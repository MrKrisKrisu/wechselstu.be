<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashRegisterApiTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_register_endpoints(): void {
        $register = CashRegister::factory()->create();

        $this->getJson('/api/cash-registers')->assertUnauthorized();
        $this->postJson('/api/cash-registers', ['name' => 'Bar 1'])->assertUnauthorized();
        $this->putJson("/api/cash-registers/{$register->id}", ['name' => 'Bar Updated'])->assertUnauthorized();
        $this->postJson("/api/cash-registers/{$register->id}/reset-token")->assertUnauthorized();
    }

    #[Test]
    public function admin_can_list_registers(): void {
        Passport::actingAs(User::factory()->create());
        CashRegister::factory()->count(3)->create();

        $response = $this->getJson('/api/cash-registers');
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function admin_can_create_register(): void {
        Passport::actingAs(User::factory()->create());

        $response = $this->postJson('/api/cash-registers', ['name' => 'Bar Neue']);

        $response->assertCreated()
                 ->assertJsonFragment(['name' => 'Bar Neue']);

        $this->assertDatabaseHas('cash_registers', ['name' => 'Bar Neue']);
    }

    #[Test]
    public function admin_can_update_register_name(): void {
        Passport::actingAs(User::factory()->create());
        $register = CashRegister::factory()->create(['name' => 'Altname']);

        $response = $this->putJson("/api/cash-registers/{$register->id}", ['name' => 'Neu']);

        $response->assertOk()
                 ->assertJsonFragment(['name' => 'Neu']);

        $this->assertDatabaseHas('cash_registers', ['id' => $register->id, 'name' => 'Neu']);
    }

    #[Test]
    public function admin_can_reset_register_token(): void {
        Passport::actingAs(User::factory()->create());
        $register = CashRegister::factory()->create(['token' => 'old-token']);

        $response = $this->postJson("/api/cash-registers/{$register->id}/reset-token");

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertNotEquals('old-token', $register->fresh()->token);
    }

    #[Test]
    public function returns_404_for_invalid_register_id(): void {
        Passport::actingAs(User::factory()->create());

        $invalidId = '00000000-0000-0000-0000-000000000000';

        $this->putJson("/api/cash-registers/{$invalidId}", ['name' => 'Foo'])->assertNotFound();
        $this->postJson("/api/cash-registers/{$invalidId}/reset-token")->assertNotFound();
    }
}
