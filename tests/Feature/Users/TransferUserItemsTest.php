<?php

namespace Tests\Feature\Users;

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class TransferUserItemsTest extends TestCase
{
    public function test_transfer_page_requires_authentication(): void
    {
        User::factory()->create();
        $source = User::factory()->create();

        $this->get(route('users.transfer.show', $source))
            ->assertRedirect(route('login'));
    }

    public function test_transfer_page_requires_checkout_permission(): void
    {
        $source = User::factory()->create();

        $this->actingAs(User::factory()->viewUsers()->create())
            ->get(route('users.transfer.show', $source))
            ->assertForbidden();
    }

    public function test_transfer_page_renders_when_source_has_items(): void
    {
        $source = User::factory()->create();
        Asset::factory()->create(['assigned_to' => $source->id, 'assigned_type' => User::class]);

        $this->actingAs($this->transferActor())
            ->get(route('users.transfer.show', $source))
            ->assertOk()
            ->assertViewIs('users.transfer');
    }

    public function test_transfer_page_redirects_when_source_has_no_items(): void
    {
        $source = User::factory()->create();

        $this->actingAs($this->transferActor())
            ->get(route('users.transfer.show', $source))
            ->assertRedirect(route('users.show', $source));
    }

    public function test_transfer_moves_asset_from_source_to_target(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        $asset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);

        $response = $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'asset_ids' => [$asset->id],
                'note' => 'employee offboarding',
            ]);

        $response->assertRedirect(route('users.show', $target));

        $asset->refresh();
        $this->assertSame($target->id, $asset->assigned_to);
        $this->assertSame(User::class, $asset->assigned_type);

        // Both a checkin and a checkout should be logged for this transfer
        // so the audit trail shows the full move rather than a single event.
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkin from',
            'target_id' => $source->id,
            'target_type' => User::class,
            'item_id' => $asset->id,
            'item_type' => Asset::class,
        ]);
        $this->assertDatabaseHas('action_logs', [
            'action_type' => 'checkout',
            'target_id' => $target->id,
            'target_type' => User::class,
            'item_id' => $asset->id,
            'item_type' => Asset::class,
        ]);
    }

    public function test_transfer_moves_accessory_from_source_to_target(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        $accessory = Accessory::factory()->create();
        $checkout = AccessoryCheckout::create([
            'accessory_id' => $accessory->id,
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'accessory_checkout_ids' => [$checkout->id],
                'note' => 'employee offboarding',
            ])
            ->assertRedirect(route('users.show', $target));

        $this->assertDatabaseMissing('accessories_checkout', ['id' => $checkout->id]);
        $this->assertDatabaseHas('accessories_checkout', [
            'accessory_id' => $accessory->id,
            'assigned_to' => $target->id,
            'assigned_type' => User::class,
        ]);
    }

    public function test_transfer_moves_reassignable_license_seat_from_source_to_target(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        $license = License::factory()->create(['reassignable' => 1]);
        $seat = LicenseSeat::factory()->assignedToUser($source)->create(['license_id' => $license->id]);

        $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'license_seat_ids' => [$seat->id],
                'note' => 'transferring license seat',
            ])
            ->assertRedirect(route('users.show', $target));

        $seat->refresh();
        $this->assertSame($target->id, $seat->assigned_to);
    }

    public function test_transfer_skips_non_reassignable_license_seat(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        $license = License::factory()->create(['reassignable' => 0]);
        $seat = LicenseSeat::factory()->assignedToUser($source)->create(['license_id' => $license->id]);

        // Non-reassignable licenses stay put by design. The seat must NOT
        // move even if the client somehow submits its id.
        $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'license_seat_ids' => [$seat->id],
                'note' => 'attempt to move non-reassignable license',
            ])
            ->assertRedirect(route('users.show', $target));

        $seat->refresh();
        $this->assertSame($source->id, $seat->assigned_to);
    }

    public function test_transfer_leaves_unselected_items_alone(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        $selectedAsset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);
        $unselectedAsset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);

        $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'asset_ids' => [$selectedAsset->id],
                'note' => 'transferring one item',
            ]);

        $selectedAsset->refresh();
        $unselectedAsset->refresh();

        $this->assertSame($target->id, $selectedAsset->assigned_to);
        $this->assertSame($source->id, $unselectedAsset->assigned_to);
    }

    public function test_transfer_rejects_same_source_and_target(): void
    {
        $source = User::factory()->create();
        $asset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);

        $this->actingAs($this->transferActor())
            ->from(route('users.transfer.show', $source))
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $source->id,
                'asset_ids' => [$asset->id],
                'note' => 'accidental self-target',
            ])
            ->assertRedirect(route('users.transfer.show', $source))
            ->assertSessionHasErrors('target_user_id');

        $asset->refresh();
        $this->assertSame($source->id, $asset->assigned_to);
    }

    public function test_transfer_rejects_empty_selection(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($this->transferActor())
            ->from(route('users.transfer.show', $source))
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'note' => 'nothing selected',
            ])
            ->assertRedirect(route('users.transfer.show', $source))
            ->assertSessionHasErrors('asset_ids');
    }

    public function test_transfer_rejects_empty_note(): void
    {
        $source = User::factory()->create();
        $target = User::factory()->create();
        $asset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);

        $this->actingAs($this->transferActor())
            ->from(route('users.transfer.show', $source))
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'asset_ids' => [$asset->id],
                // note deliberately omitted
            ])
            ->assertRedirect(route('users.transfer.show', $source))
            ->assertSessionHasErrors('note');

        $asset->refresh();
        $this->assertSame($source->id, $asset->assigned_to);
    }

    public function test_transfer_ignores_asset_not_actually_assigned_to_source(): void
    {
        // Someone tampering with the form to pass an asset ID that
        // doesn't belong to the source user should not be able to
        // hijack the transfer flow to reassign arbitrary assets.
        $source = User::factory()->create();
        $target = User::factory()->create();
        $otherOwner = User::factory()->create();

        $sourceAsset = Asset::factory()->create([
            'assigned_to' => $source->id,
            'assigned_type' => User::class,
        ]);
        $foreignAsset = Asset::factory()->create([
            'assigned_to' => $otherOwner->id,
            'assigned_type' => User::class,
        ]);

        $this->actingAs($this->transferActor())
            ->post(route('users.transfer.store', $source), [
                'target_user_id' => $target->id,
                'asset_ids' => [$sourceAsset->id, $foreignAsset->id],
                'note' => 'attempting cross-user transfer',
            ]);

        $sourceAsset->refresh();
        $foreignAsset->refresh();

        $this->assertSame($target->id, $sourceAsset->assigned_to);
        $this->assertSame($otherOwner->id, $foreignAsset->assigned_to);
    }

    private function transferActor(): User
    {
        return User::factory()
            ->viewUsers()
            ->checkinAssets()
            ->checkoutAssets()
            ->create();
    }
}
