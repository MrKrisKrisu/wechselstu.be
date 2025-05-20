<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkOrderApiTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function public_can_create_overflow_work_order() {
        $register = CashRegister::factory()->create();

        $response = $this->postJson("/api/cash-registers/{$register->id}/work-orders?token={$register->token}", [
            'type'  => 'overflow',
            'notes' => 'register too full',
        ]);

        $response->assertCreated()
                 ->assertJsonFragment(['type' => 'overflow', 'notes' => 'register too full']);

        $this->assertDatabaseHas('work_orders', ['type' => 'overflow', 'cash_register_id' => $register->id]);
    }

    #[Test]
    public function public_fails_with_invalid_type() {
        $register = CashRegister::factory()->create();

        $response = $this->postJson("/api/cash-registers/{$register->id}/work-orders?token={$register->token}", [
            'type' => 'invalid',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function public_can_create_change_request_with_items() {
        $register = CashRegister::factory()->create();

        $payload = [
            'type'  => 'change_request',
            'items' => [
                ['denomination' => 50, 'quantity' => 2],
                ['denomination' => 10, 'quantity' => 5],
            ],
        ];

        $response = $this->postJson("/api/cash-registers/{$register->id}/work-orders?token={$register->token}", $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('change_request_items', ['denomination' => 50, 'quantity' => 2]);
    }

    #[Test]
    public function public_fails_when_items_missing_for_change_request() {
        $register = CashRegister::factory()->create();

        $response = $this->postJson("/api/cash-registers/{$register->id}/work-orders?token={$register->token}", [
            'type' => 'change_request',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_list_all_work_orders() {
        $user = User::factory()->create();
        Passport::actingAs($user);
        WorkOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/work-orders');

        $response->assertOk()
                 ->assertJsonCount(3);
    }

    #[Test]
    public function admin_can_filter_work_orders_by_status() {
        $user = User::factory()->create();
        Passport::actingAs($user);
        WorkOrder::factory()->create(['status' => 'pending']);
        WorkOrder::factory()->create(['status' => 'done']);

        $response = $this->getJson('/api/work-orders?status=done');

        $response->assertOk()
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['status' => 'done']);
    }

    #[Test]
    public function admin_cannot_use_invalid_status_filter() {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/work-orders?status=foo');

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_can_update_work_order_status() {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $workOrder = WorkOrder::factory()->create(['status' => 'pending']);

        $response = $this->putJson("/api/work-orders/{$workOrder->id}", [
            'status' => 'done',
        ]);

        $response->assertOk()
                 ->assertJsonFragment(['status' => 'done']);

        $this->assertDatabaseHas('work_orders', ['id' => $workOrder->id, 'status' => 'done']);
    }

    #[Test]
    public function admin_cannot_update_with_invalid_status() {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $workOrder = WorkOrder::factory()->create();

        $response = $this->putJson("/api/work-orders/{$workOrder->id}", [
            'status' => 'invalid',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function admin_update_returns_404_for_invalid_id() {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->putJson(
            '/api/work-orders/00000000-0000-0000-0000-000000000000',
            ['status' => 'done']
        );

        $response->assertNotFound();
    }
}
