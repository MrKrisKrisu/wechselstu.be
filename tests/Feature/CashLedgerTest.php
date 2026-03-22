<?php

namespace Tests\Feature;

use App\Enums\CashEntryType;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\CashClosing;
use App\Models\CashEntry;
use App\Models\Station;
use App\Models\Ticket;
use App\Models\TicketDenomination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CashLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function makeStation(array $attrs = []): Station
    {
        return Station::create(array_merge(['name' => 'Testkasse', 'location' => 'Bar'], $attrs));
    }

    private function makeEntry(array $attrs = []): CashEntry
    {
        $entry = CashEntry::create(array_merge([
            'type' => CashEntryType::Deposit->value,
            'amount_cents' => 10000,
            'created_by' => $this->user->id,
        ], $attrs));

        // Allow explicit timestamp overrides (Eloquent auto-sets them, update after creation)
        $overrides = array_intersect_key($attrs, array_flip(['created_at', 'updated_at']));
        if ($overrides) {
            $entry->timestamps = false;
            $entry->forceFill($overrides)->save();
            $entry->timestamps = true;
            $entry->refresh();
        }

        return $entry;
    }

    private function makeTicket(Station $station, array $attrs = []): Ticket
    {
        return Ticket::create(array_merge([
            'station_id' => $station->id,
            'type' => TicketType::CashFull->value,
            'status' => TicketStatus::Done->value,
            'done_at' => now(),
        ], $attrs));
    }

    private function makeClosing(array $attrs = []): CashClosing
    {
        return CashClosing::create(array_merge([
            'label' => 'Tagesabschluss',
            'closing_date' => now()->toDateString(),
            'locked_until' => now(),
            'balance_cents' => 0,
            'created_by' => $this->user->id,
        ], $attrs));
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/finance/cash-ledger')->assertUnauthorized();
    }

    public function test_store_entry_requires_auth(): void
    {
        $this->postJson('/api/finance/cash-ledger/entries', [])->assertUnauthorized();
    }

    public function test_store_reversal_requires_auth(): void
    {
        $entry = $this->makeEntry();
        $this->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal")->assertUnauthorized();
    }

    public function test_store_closing_requires_auth(): void
    {
        $this->postJson('/api/finance/cash-ledger/closings', [])->assertUnauthorized();
    }

    public function test_export_csv_requires_auth(): void
    {
        $this->getJson('/api/finance/cash-ledger/export/csv')->assertUnauthorized();
    }

    public function test_export_pdf_requires_auth(): void
    {
        $this->getJson('/api/finance/cash-ledger/export/pdf')->assertUnauthorized();
    }

    public function test_index_returns_empty_state(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->assertOk()
            ->assertJsonStructure(['entries', 'closings', 'stations', 'balance_cents', 'locked_until', 'suggestions'])
            ->assertJson([
                'entries' => [],
                'closings' => [],
                'stations' => [],
                'balance_cents' => 0,
                'locked_until' => null,
                'suggestions' => [],
            ]);
    }

    public function test_index_calculates_total_balance(): void
    {
        $this->makeEntry(['amount_cents' => 100000]);
        $this->makeEntry(['amount_cents' => -30000]);

        $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->assertOk()
            ->assertJsonPath('balance_cents', 70000);
    }

    public function test_index_returns_entries_in_ascending_order(): void
    {
        $e1 = $this->makeEntry(['created_at' => now()->subHour()]);
        $e2 = $this->makeEntry(['created_at' => now()]);

        $response = $this->actingAs($this->user)->getJson('/api/finance/cash-ledger');

        $ids = collect($response->json('entries'))->pluck('id')->all();
        $this->assertEquals([$e1->id, $e2->id], $ids);
    }

    public function test_index_serializes_entry_with_all_relations(): void
    {
        $station = $this->makeStation(['name' => 'Gegenkasse']);
        $ticket = $this->makeTicket($station, ['done_at' => now()]);
        $entry = $this->makeEntry([
            'type' => CashEntryType::TransferIn->value,
            'amount_cents' => 50000,
            'description' => 'Abschöpfung Test',
            'ticket_id' => $ticket->id,
            'counterpart_station_id' => $station->id,
        ]);

        $data = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->assertOk()
            ->json('entries.0');

        $this->assertEquals($entry->id, $data['id']);
        $this->assertEquals('transfer_in', $data['type']);
        $this->assertEquals('Abschöpfung', $data['type_label']);
        $this->assertEquals(50000, $data['amount_cents']);
        $this->assertEquals('Abschöpfung Test', $data['description']);
        $this->assertEquals($this->user->name, $data['created_by']);
        $this->assertEquals($ticket->id, $data['ticket_id']);
        $this->assertNotNull($data['ticket_done_at']);
        $this->assertEquals($station->id, $data['counterpart_station_id']);
        $this->assertEquals('Gegenkasse', $data['counterpart_station_name']);
        $this->assertNull($data['reversed_at']);
        $this->assertNull($data['reversed_by_entry_id']);
    }

    public function test_index_serializes_entry_without_optional_relations(): void
    {
        $this->makeEntry(['description' => null, 'ticket_id' => null, 'counterpart_station_id' => null]);

        $data = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('entries.0');

        $this->assertNull($data['description']);
        $this->assertNull($data['ticket_id']);
        $this->assertNull($data['ticket_done_at']);
        $this->assertNull($data['counterpart_station_id']);
        $this->assertNull($data['counterpart_station_name']);
    }

    public function test_index_serializes_reversed_entry(): void
    {
        $reversal = $this->makeEntry();
        $entry = $this->makeEntry([
            'reversed_at' => now(),
            'reversed_by_entry_id' => $reversal->id,
        ]);

        $data = collect($this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('entries'))
            ->firstWhere('id', $entry->id);

        $this->assertNotNull($data['reversed_at']);
        $this->assertEquals($reversal->id, $data['reversed_by_entry_id']);
    }

    public function test_index_calculates_station_balance(): void
    {
        $station = $this->makeStation();
        $this->makeEntry(['counterpart_station_id' => $station->id, 'amount_cents' => -30000]);
        $this->makeEntry(['counterpart_station_id' => $station->id, 'amount_cents' => -20000]);

        $response = $this->actingAs($this->user)->getJson('/api/finance/cash-ledger');

        $stationData = collect($response->json('stations'))->firstWhere('id', $station->id);
        // balance = SUM(-amount_cents) = 30000 + 20000
        $this->assertEquals(50000, $stationData['balance_cents']);
    }

    public function test_index_excludes_reversed_entries_from_station_balance(): void
    {
        $station = $this->makeStation();
        $reversed = $this->makeEntry(['counterpart_station_id' => $station->id, 'amount_cents' => -30000]);
        $reversed->update(['reversed_at' => now()]);
        $this->makeEntry(['counterpart_station_id' => $station->id, 'amount_cents' => -20000]);

        $stationData = collect(
            $this->actingAs($this->user)->getJson('/api/finance/cash-ledger')->json('stations')
        )->firstWhere('id', $station->id);

        $this->assertEquals(20000, $stationData['balance_cents']);
    }

    public function test_index_returns_locked_until_from_last_closing(): void
    {
        $this->makeClosing(['locked_until' => now()->subDay()]);
        $last = $this->makeClosing(['locked_until' => now()->addHour()]);

        $response = $this->actingAs($this->user)->getJson('/api/finance/cash-ledger');

        $this->assertEquals($last->locked_until->toIso8601String(), $response->json('locked_until'));
    }

    public function test_index_serializes_closing_fields(): void
    {
        $closing = $this->makeClosing([
            'label' => 'Abschluss März',
            'closing_date' => '2026-03-22',
            'balance_cents' => 175000,
        ]);

        $data = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('closings.0');

        $this->assertEquals($closing->id, $data['id']);
        $this->assertEquals('Abschluss März', $data['label']);
        $this->assertEquals('2026-03-22', $data['closing_date']);
        $this->assertEquals(175000, $data['balance_cents']);
        $this->assertEquals($this->user->name, $data['created_by']);
        $this->assertNotNull($data['locked_until']);
        $this->assertNotNull($data['created_at']);
    }

    public function test_index_suggests_cash_full_ticket(): void
    {
        $station = $this->makeStation();
        $ticket = $this->makeTicket($station, ['type' => TicketType::CashFull->value]);

        $suggestions = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('suggestions');

        $this->assertCount(1, $suggestions);
        $this->assertEquals($ticket->id, $suggestions[0]['ticket_id']);
        $this->assertEquals('cash_full', $suggestions[0]['ticket_type']);
        $this->assertEquals('transfer_in', $suggestions[0]['suggested_type']);
        $this->assertNull($suggestions[0]['suggested_amount_cents']);
        $this->assertEquals($station->name, $suggestions[0]['station_name']);
    }

    public function test_index_suggests_change_request_ticket_with_denomination_amount(): void
    {
        $station = $this->makeStation();
        $ticket = $this->makeTicket($station, [
            'type' => TicketType::ChangeRequest->value,
            'done_at' => now(),
        ]);
        TicketDenomination::create(['ticket_id' => $ticket->id, 'denomination_cents' => 200, 'quantity' => 10]);
        TicketDenomination::create(['ticket_id' => $ticket->id, 'denomination_cents' => 100, 'quantity' => 5]);

        $suggestions = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('suggestions');

        $this->assertCount(1, $suggestions);
        $this->assertEquals('transfer_out', $suggestions[0]['suggested_type']);
        // -(200*10 + 100*5) = -2500
        $this->assertEquals(-2500, $suggestions[0]['suggested_amount_cents']);
    }

    public function test_index_excludes_already_linked_tickets_from_suggestions(): void
    {
        $station = $this->makeStation();
        $ticket = $this->makeTicket($station);
        $this->makeEntry(['ticket_id' => $ticket->id]);

        $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->assertJsonPath('suggestions', []);
    }

    public function test_index_suggestions_exclude_non_done_tickets(): void
    {
        $station = $this->makeStation();
        $this->makeTicket($station, ['status' => TicketStatus::Open->value, 'done_at' => null]);

        $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->assertJsonPath('suggestions', []);
    }

    public function test_index_suggestions_filtered_by_locked_until(): void
    {
        $station = $this->makeStation();
        $lockTime = now()->subDay();

        // Done before lock: excluded
        $this->makeTicket($station, ['done_at' => $lockTime->copy()->subDay()]);
        // Done after lock: included
        $this->makeTicket($station, ['done_at' => now()]);

        $this->makeClosing(['locked_until' => $lockTime]);

        $suggestions = $this->actingAs($this->user)
            ->getJson('/api/finance/cash-ledger')
            ->json('suggestions');

        $this->assertCount(1, $suggestions);
    }

    public function test_store_entry_creates_entry_and_returns_201(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', [
                'type' => 'deposit',
                'amount_cents' => 50000,
            ])
            ->assertCreated()
            ->assertJsonPath('entry.type', 'deposit')
            ->assertJsonPath('entry.amount_cents', 50000)
            ->assertJsonPath('entry.created_by', $this->user->name);

        $this->assertDatabaseHas('cash_entries', [
            'type' => 'deposit',
            'amount_cents' => 50000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_entry_with_all_optional_fields(): void
    {
        $station = $this->makeStation();
        $ticket = $this->makeTicket($station);

        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', [
                'type' => 'transfer_in',
                'amount_cents' => 10000,
                'description' => 'Testbuchung',
                'ticket_id' => $ticket->id,
                'counterpart_station_id' => $station->id,
            ])
            ->assertCreated()
            ->assertJsonPath('entry.description', 'Testbuchung')
            ->assertJsonPath('entry.ticket_id', $ticket->id)
            ->assertJsonPath('entry.counterpart_station_id', $station->id)
            ->assertJsonPath('entry.counterpart_station_name', $station->name);
    }

    public function test_store_entry_sets_created_by_from_auth_user(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', ['type' => 'deposit', 'amount_cents' => 100]);

        $this->assertDatabaseHas('cash_entries', ['created_by' => $this->user->id]);
        $this->assertDatabaseMissing('cash_entries', ['created_by' => $other->id]);
    }

    public function test_store_entry_validation_type_required(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', ['amount_cents' => 100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_store_entry_validation_type_must_be_valid(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', ['type' => 'invalid', 'amount_cents' => 100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_store_entry_validation_amount_required(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', ['type' => 'deposit'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount_cents');
    }

    public function test_store_entry_validation_amount_not_zero(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', ['type' => 'deposit', 'amount_cents' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount_cents');
    }

    public function test_store_entry_validation_ticket_id_must_exist(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', [
                'type' => 'deposit',
                'amount_cents' => 100,
                'ticket_id' => '00000000-0000-0000-0000-000000000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ticket_id');
    }

    public function test_store_entry_validation_counterpart_station_must_exist(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/entries', [
                'type' => 'deposit',
                'amount_cents' => 100,
                'counterpart_station_id' => '00000000-0000-0000-0000-000000000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('counterpart_station_id');
    }

    public function test_store_reversal_creates_reversal_entry(): void
    {
        $station = $this->makeStation();
        $entry = $this->makeEntry([
            'amount_cents' => 50000,
            'description' => 'Original',
            'counterpart_station_id' => $station->id,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal")
            ->assertCreated()
            ->assertJsonPath('entry.type', 'reversal')
            ->assertJsonPath('entry.amount_cents', -50000)
            ->assertJsonPath('entry.counterpart_station_id', $station->id);

        $this->assertNotNull($entry->fresh()->reversed_at);
        $this->assertNotNull($entry->fresh()->reversed_by_entry_id);
    }

    public function test_store_reversal_description_contains_original_date(): void
    {
        $entry = $this->makeEntry(['description' => 'Meine Buchung']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal");

        $description = $response->json('entry.description');
        $this->assertStringContainsString('Storno', $description);
        $this->assertStringContainsString('Meine Buchung', $description);
    }

    public function test_store_reversal_returns_422_if_already_reversed(): void
    {
        $entry = $this->makeEntry(['reversed_at' => now()]);

        $this->actingAs($this->user)
            ->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Buchung wurde bereits storniert.');
    }

    public function test_store_reversal_returns_422_if_entry_is_locked(): void
    {
        $entry = $this->makeEntry(['created_at' => now()->subHours(2)]);
        $this->makeClosing(['locked_until' => now()->subHour()]);

        $this->actingAs($this->user)
            ->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Buchungen vor dem letzten Abschluss können nicht storniert werden.');
    }

    public function test_store_reversal_allowed_for_entry_after_lock(): void
    {
        $this->makeClosing(['locked_until' => now()->subHour()]);
        $entry = $this->makeEntry(['created_at' => now()]);

        $this->actingAs($this->user)
            ->postJson("/api/finance/cash-ledger/entries/{$entry->id}/reversal")
            ->assertCreated();
    }

    public function test_store_closing_creates_closing_and_returns_201(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'label' => 'Tagesabschluss 22.03.',
                'closing_date' => '2026-03-22',
                'locked_until' => '2026-03-22T23:59:00Z',
            ])
            ->assertCreated()
            ->assertJsonPath('closing.label', 'Tagesabschluss 22.03.')
            ->assertJsonPath('closing.closing_date', '2026-03-22')
            ->assertJsonPath('closing.created_by', $this->user->name);

        $this->assertDatabaseHas('cash_closings', [
            'label' => 'Tagesabschluss 22.03.',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_closing_calculates_balance_snapshot(): void
    {
        $lockTime = Carbon::parse('2026-03-22T12:00:00Z');

        $this->makeEntry(['amount_cents' => 100000, 'created_at' => $lockTime->copy()->subHour()]);
        $this->makeEntry(['amount_cents' => -30000, 'created_at' => $lockTime->copy()->subMinutes(30)]);
        // Entry after lock: must not be included
        $this->makeEntry(['amount_cents' => 99999, 'created_at' => $lockTime->copy()->addHour()]);

        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'label' => 'Test',
                'closing_date' => $lockTime->toDateString(),
                'locked_until' => $lockTime->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('closing.balance_cents', 70000);
    }

    public function test_store_closing_validation_label_required(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'closing_date' => '2026-03-22',
                'locked_until' => '2026-03-22T23:59:00Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('label');
    }

    public function test_store_closing_validation_closing_date_required(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'label' => 'Test',
                'locked_until' => '2026-03-22T23:59:00Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('closing_date');
    }

    public function test_store_closing_validation_locked_until_required(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'label' => 'Test',
                'closing_date' => '2026-03-22',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locked_until');
    }

    public function test_store_closing_validation_locked_until_invalid_format(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/finance/cash-ledger/closings', [
                'label' => 'Test',
                'closing_date' => '2026-03-22',
                'locked_until' => '22.03.2026 23:59',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('locked_until');
    }

    public function test_export_csv_returns_correct_headers(): void
    {
        $response = $this->actingAs($this->user)->get('/api/finance/cash-ledger/export/csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('kassenbuch-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contains_header_row(): void
    {
        $content = $this->actingAs($this->user)
            ->get('/api/finance/cash-ledger/export/csv')
            ->getContent();

        $this->assertStringContainsString('Datum;Typ;Gegenkasse;Betrag', $content);
    }

    public function test_export_csv_contains_entry_data(): void
    {
        $station = $this->makeStation(['name' => 'CSV Kasse']);
        $this->makeEntry([
            'type' => CashEntryType::Deposit->value,
            'amount_cents' => 12345,
            'description' => 'CSV Testbuchung',
            'counterpart_station_id' => $station->id,
        ]);

        $content = $this->actingAs($this->user)
            ->get('/api/finance/cash-ledger/export/csv')
            ->getContent();

        $this->assertStringContainsString('CSV Kasse', $content);
        $this->assertStringContainsString('123,45', $content);
        $this->assertStringContainsString('CSV Testbuchung', $content);
        $this->assertStringContainsString($this->user->name, $content);
    }

    public function test_export_csv_includes_running_balance(): void
    {
        $this->makeEntry(['amount_cents' => 50000]);
        $this->makeEntry(['amount_cents' => -10000]);

        $content = $this->actingAs($this->user)
            ->get('/api/finance/cash-ledger/export/csv')
            ->getContent();

        // Running balance after first entry: 500,00
        $this->assertStringContainsString('500,00', $content);
        // Running balance after second entry: 400,00
        $this->assertStringContainsString('400,00', $content);
    }

    public function test_export_csv_shows_reversal_date_for_reversed_entry(): void
    {
        $reversedAt = now();
        $this->makeEntry(['reversed_at' => $reversedAt]);

        $content = $this->actingAs($this->user)
            ->get('/api/finance/cash-ledger/export/csv')
            ->getContent();

        $this->assertStringContainsString($reversedAt->format('d.m.Y'), $content);
    }

    public function test_export_pdf_returns_correct_headers(): void
    {
        $response = $this->actingAs($this->user)->get('/api/finance/cash-ledger/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('kassenbuch-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_pdf_renders_with_entries_and_closings(): void
    {
        $this->makeEntry(['amount_cents' => 50000]);
        $this->makeClosing();

        $response = $this->actingAs($this->user)->get('/api/finance/cash-ledger/export/pdf');

        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
    }
}
