<?php

namespace App\Console\Commands;

use App\Enum\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\MatrixService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatrixReactionListener extends Command {
    const WHO_AM_I_CACHE_KEY = 'matrix_who_am_i';
    protected             $signature   = 'matrix:listen-reactions {--count=1} {--pause=500}';
    protected             $description = 'Listens for Matrix emoji reactions and updates task states accordingly';
    private MatrixService $matrixService;

    public function __construct(MatrixService $matrixService) {
        parent::__construct();
        $this->matrixService = $matrixService;
    }

    public function handle(): int {
        $count   = (int)$this->option('count');
        $pauseMs = (int)$this->option('pause');

        $this->cacheWhoAmI();

        for($run = 0; $run < $count; $run++) {
            $this->runSyncCycle();
            usleep($pauseMs * 1000);
        }
        return 0;
    }

    private function cacheWhoAmI(): void {
        $matrix = new MatrixService();
        $userId = $matrix->whoAmI();
        Cache::remember(self::WHO_AM_I_CACHE_KEY, now()->addDay(), fn() => $userId);
        $this->info('Current user ID is: ' . $userId);
    }

    private function runSyncCycle(): int {
        $this->info('Running one Matrix sync cycle...');
        $since = Cache::get('matrix_sync_since');

        $url = $this->matrixService->getHomeserverUrl() . '/_matrix/client/v3/sync?timeout=1000';
        if($since) {
            $url .= '&since=' . urlencode($since);
        }

        $response = $this->matrixService->sendSyncRequest($url);
        if(!$response) {
            $this->warn('Matrix sync failed.');
            return 1;
        }

        $data = $response->json();
        Cache::put('matrix_sync_since', $data['next_batch'] ?? null);

        foreach(($data['rooms']['join'] ?? []) as $room) {
            foreach(($room['timeline']['events'] ?? []) as $event) {
                if(($event['type'] ?? '') === 'm.reaction') {
                    $this->handleReactionEvent($event);
                }
            }
        }

        return 0;
    }

    private function handleReactionEvent(array $event): void {
        $currentUser = Cache::get(self::WHO_AM_I_CACHE_KEY);
        if($event['sender'] === $currentUser) {
            $this->info("Ignoring reaction from another user: {$event['sender']}");
            return;
        }

        $reactionEventId = $event['event_id'] ?? null;
        if(!$reactionEventId) return;
        $cacheKey = 'matrix_reaction_processed_' . $reactionEventId;
        if(Cache::has($cacheKey)) {
            $this->info("Skipped already processed reaction event: {$reactionEventId}");
            return;
        }
        Cache::put($cacheKey, true, now()->addHour());

        $relates = $event['content']['m.relates_to'] ?? null;
        if(!$relates || ($relates['rel_type'] ?? '') !== 'm.annotation') return;

        $targetEventId = $relates['event_id'] ?? '';
        $emoji         = $relates['key'] ?? '';

        $status = WorkOrderStatus::getStatusByEmoji($emoji);

        if(!$status) {
            $this->info("Ignored emoji: {$emoji}");
            return;
        }

        $workOrder = WorkOrder::where('event_id', $targetEventId)->first();

        if(!$workOrder) {
            Log::warning('No work order found for Matrix event ID', ['event_id' => $targetEventId]);
            return;
        }

        $workOrder->status = $status;
        $workOrder->save();

        $this->info("Updated task {$workOrder->id} to status '{$status->value}' via emoji '{$emoji}'");
        Log::info('Task status updated via emoji reaction', [
            'task_id'    => $workOrder->id,
            'event_id'   => $targetEventId,
            'new_status' => $status,
        ]);
    }
}
