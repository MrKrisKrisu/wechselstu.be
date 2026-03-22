<?php

namespace Database\Seeders;

use App\Enums\CashEntryType;
use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Models\CashEntry;
use App\Models\Station;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::firstOrCreate(['email' => 'dev@dev.de'], [
            'name' => fake()->firstName(),
            'password' => Hash::make('password'),
        ]);

        $user2 = User::firstOrCreate(['email' => 'dev2@dev.de'], [
            'name' => fake()->firstName(),
            'password' => Hash::make('password'),
        ]);

        $user3 = User::firstOrCreate(['email' => 'dev3@dev.de'], [
            'name' => fake()->firstName(),
            'password' => Hash::make('password'),
        ]);

        $users = [$user1, $user2, $user3];

        $tschunk = Station::firstOrCreate(['name' => 'Tschunk Kasse', 'location' => 'Bar']);
        $snack = Station::firstOrCreate(['name' => 'Snack Kasse', 'location' => 'Bar']);
        $aussen1 = Station::firstOrCreate(['name' => 'Kasse 1', 'location' => 'Außenbar']);
        Station::firstOrCreate(['name' => 'Kasse 1', 'location' => 'Merch']);
        Station::firstOrCreate(['name' => 'Kasse 2', 'location' => 'Merch']);

        if (CashEntry::exists()) {
            return;
        }

        $pick = fn () => $users[array_rand($users)];
        $entryNumber = 0;
        $entry = function (array $data) use (&$entryNumber) {
            return CashEntry::create(array_merge(['entry_number' => ++$entryNumber], $data));
        };

        // Anfangsbestand Hauptkasse
        $entry([
            'type' => CashEntryType::Opening->value,
            'amount_cents' => 200_000, // 2.000 €
            'description' => 'Von Girokonto',
            'created_by' => $pick()->id,
            'created_at' => now()->subHours(8),
            'updated_at' => now()->subHours(8),
        ]);
        // HK: 2.000 €

        // Morgens: Kassenöffnungen mit Wechselgeld
        // HK gibt aus: 300 + 200 + 150 = 650 €  -> HK: 1.350 €
        $entry([
            'type' => CashEntryType::CashDrawerOpen->value,
            'amount_cents' => -30_000, // 300 €
            'description' => 'Wechselgeld',
            'created_by' => $pick()->id,
            'counterpart_station_id' => $tschunk->id,
            'created_at' => now()->subHours(7),
            'updated_at' => now()->subHours(7),
        ]);

        $entry([
            'type' => CashEntryType::CashDrawerOpen->value,
            'amount_cents' => -20_000, // 200 €
            'description' => 'Wechselgeld',
            'created_by' => $pick()->id,
            'counterpart_station_id' => $snack->id,
            'created_at' => now()->subHours(7),
            'updated_at' => now()->subHours(7),
        ]);

        $entry([
            'type' => CashEntryType::CashDrawerOpen->value,
            'amount_cents' => -15_000, // 150 €
            'description' => 'Wechselgeld',
            'created_by' => $pick()->id,
            'counterpart_station_id' => $aussen1->id,
            'created_at' => now()->subHours(7),
            'updated_at' => now()->subHours(7),
        ]);

        // Mittags: Wechselgeld-Anfrage von Tschunk (Ticket), danach buchen
        $ticketWechsel = Ticket::create([
            'station_id' => $tschunk->id,
            'type' => TicketType::ChangeRequest->value,
            'status' => TicketStatus::Done->value,
            'message' => 'Bitte mehr 1€ und 2€ Münzen',
            'assigned_to' => $user2->id,
            'accepted_at' => now()->subHours(4),
            'done_at' => now()->subHours(3)->subMinutes(40),
            'created_at' => now()->subHours(4)->subMinutes(15),
            'updated_at' => now()->subHours(3)->subMinutes(40),
        ]);

        $entry([
            'type' => CashEntryType::TransferOut->value,
            'amount_cents' => -8_000, // 80 €
            'description' => 'Wechselgeld ausgegeben',
            'created_by' => $user2->id,
            'counterpart_station_id' => $tschunk->id,
            'ticket_id' => $ticketWechsel->id,
            'created_at' => now()->subHours(3)->subMinutes(35),
            'updated_at' => now()->subHours(3)->subMinutes(35),
        ]);
        // HK: 1.350 - 80 = 1.270 €, Tschunk-Float: 380 €

        // Mittags: Abschöpfung Tschunk (Kasse voll, Ticket)
        $ticketVoll = Ticket::create([
            'station_id' => $tschunk->id,
            'type' => TicketType::CashFull->value,
            'status' => TicketStatus::Done->value,
            'message' => null,
            'assigned_to' => $user1->id,
            'accepted_at' => now()->subHours(3),
            'done_at' => now()->subHours(3)->addMinutes(10),
            'created_at' => now()->subHours(3)->subMinutes(5),
            'updated_at' => now()->subHours(3)->addMinutes(10),
        ]);

        $entry([
            'type' => CashEntryType::TransferIn->value,
            'amount_cents' => 48_000, // 480 €
            'description' => 'Abschöpfung Umsatz Mittagsschicht',
            'created_by' => $user1->id,
            'counterpart_station_id' => $tschunk->id,
            'ticket_id' => $ticketVoll->id,
            'created_at' => now()->subHours(3)->addMinutes(12),
            'updated_at' => now()->subHours(3)->addMinutes(12),
        ]);
        // HK: 1.270 + 480 = 1.750 €, Tschunk-Float: 300 €

        // Abschöpfung Snack (ohne Ticket)
        $entry([
            'type' => CashEntryType::TransferIn->value,
            'amount_cents' => 22_000, // 220 €
            'description' => 'Abschöpfung Umsatz Mittagsschicht',
            'created_by' => $pick()->id,
            'counterpart_station_id' => $snack->id,
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);
        // HK: 1.750 + 220 = 1.970 €, Snack-Float: 200 €

        // Außenbar: Wechselgeld-Anfrage (Ticket), danach buchen
        $ticketAussen = Ticket::create([
            'station_id' => $aussen1->id,
            'type' => TicketType::ChangeRequest->value,
            'status' => TicketStatus::Done->value,
            'message' => 'Kleingeld geht zur Neige',
            'assigned_to' => $user3->id,
            'accepted_at' => now()->subHours(2),
            'done_at' => now()->subHours(1)->subMinutes(50),
            'created_at' => now()->subHours(2)->subMinutes(10),
            'updated_at' => now()->subHours(1)->subMinutes(50),
        ]);

        $entry([
            'type' => CashEntryType::TransferOut->value,
            'amount_cents' => -10_000, // 100 €
            'description' => 'Kleingeld-Nachschub',
            'created_by' => $user3->id,
            'counterpart_station_id' => $aussen1->id,
            'ticket_id' => $ticketAussen->id,
            'created_at' => now()->subHours(1)->subMinutes(45),
            'updated_at' => now()->subHours(1)->subMinutes(45),
        ]);
        // HK: 1.970 - 100 = 1.870 €, Außenbar-Float: 250 €
    }
}
