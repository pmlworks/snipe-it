<?php

namespace Tests\Feature\Accessories\Ui;

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\User;
use Tests\TestCase;

class BulkDeleteAccessoriesTest extends TestCase
{
    public function test_requires_permission()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [1, 2, 3],
            ])
            ->assertForbidden();
    }

    public function test_checked_out_accessory_is_not_bulk_deleted()
    {
        $accessory = Accessory::factory()->create();
        AccessoryCheckout::factory()->create(['accessory_id' => $accessory->id]);

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$accessory->id],
            ])
            ->assertSessionHas('multi_error_messages');

        $this->assertDatabaseHas('accessories', ['id' => $accessory->id, 'deleted_at' => null]);
    }

    public function test_deletable_accessories_are_bulk_deleted()
    {
        $accessory1 = Accessory::factory()->create();
        $accessory2 = Accessory::factory()->create();
        $accessory3 = Accessory::factory()->create();

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$accessory1->id, $accessory2->id, $accessory3->id],
            ])
            ->assertRedirect(route('accessories.index'));

        // Accessories are soft-deleted.
        $this->assertSoftDeleted('accessories', ['id' => $accessory1->id]);
        $this->assertSoftDeleted('accessories', ['id' => $accessory2->id]);
        $this->assertSoftDeleted('accessories', ['id' => $accessory3->id]);
    }

    public function test_partial_success_deletes_the_clean_ones_and_reports_the_rest()
    {
        $deletable = Accessory::factory()->create();
        $blocked = Accessory::factory()->create();
        AccessoryCheckout::factory()->create(['accessory_id' => $blocked->id]);

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$deletable->id, $blocked->id],
            ])
            ->assertRedirect(route('accessories.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('multi_error_messages');

        $this->assertSoftDeleted('accessories', ['id' => $deletable->id]);
        $this->assertDatabaseHas('accessories', ['id' => $blocked->id, 'deleted_at' => null]);
    }

    public function test_nonexistent_ids_are_reported_and_do_not_break_the_batch()
    {
        $deletable = Accessory::factory()->create();

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$deletable->id, 999999],
            ])
            ->assertRedirect(route('accessories.index'))
            ->assertSessionHas('multi_error_messages');

        $this->assertSoftDeleted('accessories', ['id' => $deletable->id]);
    }

    public function test_bulk_success_message_pluralizes_by_count()
    {
        $solo = Accessory::factory()->create();

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$solo->id],
            ])
            ->assertSessionHas('success', trans_choice('admin/accessories/message.delete.bulk_success', 1, ['count' => 1]));

        $a = Accessory::factory()->create();
        $b = Accessory::factory()->create();
        $c = Accessory::factory()->create();

        $this->actingAs(User::factory()->deleteAccessories()->create())
            ->post(route('accessories.bulk.delete'), [
                'ids' => [$a->id, $b->id, $c->id],
            ])
            ->assertSessionHas('success', trans_choice('admin/accessories/message.delete.bulk_success', 3, ['count' => 3]));
    }
}
