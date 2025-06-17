<?php

namespace App\Console\Commands;

use App\Enum\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\MatrixService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatrixReactionListener extends Command {
    protected $signature   = 'matrix:listen-reactions';
    protected $description = 'Listens for Matrix emoji reactions and updates task states accordingly';

    private MatrixService $matrixService;

    public function __construct(MatrixService $matrixService) {
        parent::__construct();
        $this->matrixService = $matrixService;
    }

    public function handle(): int {
        $this->info('Starting Matrix Reaction Listener...');
        $since = null;

        while(true) { //TODO: I'm currently not happy here
            $url = $this->matrixService->getHomeserverUrl() . '/_matrix/client/v3/sync?timeout=30000';
            if($since) {
                $url .= '&since=' . urlencode($since);
            }

            $response = $this->matrixService->sendSyncRequest($url);
            if(!$response) {
                $this->warn('Matrix sync failed. Retrying...');
                continue;
            }

            $data  = $response->json();
            $since = $data['next_batch'] ?? null;

            foreach(($data['rooms']['join'] ?? []) as $room) {
                foreach(($room['timeline']['events'] ?? []) as $event) {
                    if(($event['type'] ?? '') === 'm.reaction') {
                        $this->handleReactionEvent($event);
                    }
                }
            }
        }

        return 0;
    }

    private function handleReactionEvent(array $event): void {
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

        $status = match ($emoji) {
            '✅️'    => WorkOrderStatus::DONE->value,
            '⏳️'    => WorkOrderStatus::IN_PROGRESS->value,
            '❌️'    => WorkOrderStatus::PENDING->value,
            default => null,
        };

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

        $this->info("Updated task {$workOrder->id} to status '{$status}' via emoji '{$emoji}'");
        Log::info('Task status updated via emoji reaction', [
            'task_id'    => $workOrder->id,
            'event_id'   => $targetEventId,
            'new_status' => $status,
        ]);
    }
}
