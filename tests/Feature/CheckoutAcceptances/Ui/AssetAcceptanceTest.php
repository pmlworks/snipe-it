<?php

namespace Tests\Feature\CheckoutAcceptances\Ui;

use App\Events\CheckoutAccepted;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssetAcceptanceTest extends TestCase
{
    public function test_asset_checkout_accept_page_renders()
    {
        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->get(route('account.accept.item', $checkoutAcceptance))
            ->assertViewIs('account.accept.create');
    }

    public function test_cannot_accept_asset_already_accepted()
    {
        Event::fake([CheckoutAccepted::class]);

        $checkoutAcceptance = CheckoutAcceptance::factory()->accepted()->create();

        $this->assertFalse($checkoutAcceptance->isPending());

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'my note',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('error');

        Event::assertNotDispatched(CheckoutAccepted::class);
    }

    public function test_cannot_accept_asset_for_another_user()
    {
        Event::fake([CheckoutAccepted::class]);

        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->assertTrue($checkoutAcceptance->isPending());

        $anotherUser = User::factory()->create();

        $this->actingAs($anotherUser)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'my note',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('error');

        $this->assertTrue($checkoutAcceptance->fresh()->isPending());

        Event::assertNotDispatched(CheckoutAccepted::class);
    }

    public function test_user_can_accept_asset()
    {
        Event::fake([CheckoutAccepted::class]);

        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->assertTrue($checkoutAcceptance->isPending());

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'my note',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('success');

        $checkoutAcceptance->refresh();

        $this->assertFalse($checkoutAcceptance->isPending());
        $this->assertNotNull($checkoutAcceptance->accepted_at);
        $this->assertNull($checkoutAcceptance->declined_at);

        Event::assertDispatched(CheckoutAccepted::class);
    }

    public function test_user_can_decline_asset()
    {
        Event::fake([CheckoutAccepted::class]);

        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->assertTrue($checkoutAcceptance->isPending());

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'declined',
                'note' => 'my note',
            ])
            ->assertRedirectToRoute('account.accept')
            ->assertSessionHas('success');

        $checkoutAcceptance->refresh();

        $this->assertFalse($checkoutAcceptance->isPending());
        $this->assertNull($checkoutAcceptance->accepted_at);
        $this->assertNotNull($checkoutAcceptance->declined_at);

        Event::assertNotDispatched(CheckoutAccepted::class);
    }

    public function test_action_logged_when_accepting_asset()
    {
        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'my note',
            ]);

        $this->assertTrue(Actionlog::query()
            ->where([
                'action_type' => 'accepted',
                'target_id' => $checkoutAcceptance->assignedTo->id,
                'target_type' => User::class,
                'note' => 'my note',
                'item_type' => Asset::class,
                'item_id' => $checkoutAcceptance->checkoutable->id,
            ])
            ->whereNotNull('action_date')
            ->exists()
        );
    }

    public function test_action_logged_when_declining_asset()
    {
        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();

        $this->actingAs($checkoutAcceptance->assignedTo)
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'declined',
                'note' => 'my note',
            ]);

        $this->assertTrue(Actionlog::query()
            ->where([
                'action_type' => 'declined',
                'target_id' => $checkoutAcceptance->assignedTo->id,
                'target_type' => User::class,
                'note' => 'my note',
                'item_type' => Asset::class,
                'item_id' => $checkoutAcceptance->checkoutable->id,
            ])
            ->whereNotNull('action_date')
            ->exists()
        );
    }

    public function test_admin_can_complete_sign_in_place_acceptance_and_is_redirected_to_selected_destination()
    {
        Event::fake([CheckoutAccepted::class]);

        $assignee = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();

        $checkoutAcceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for($assignee, 'assignedTo')
            ->for($asset, 'checkoutable')
            ->create();

        $this->actingAs($admin)
            ->withSession([
                'sign_in_place_acceptance_id' => $checkoutAcceptance->id,
                'sign_in_place_item_id' => $asset->id,
                'sign_in_place_resource_type' => 'Assets',
                'redirect_option' => 'target',
                'checkout_to_type' => 'user',
            ])
            ->post(route('account.store-acceptance', $checkoutAcceptance), [
                'asset_acceptance' => 'accepted',
                'note' => 'signed in person',
            ])
            ->assertRedirect(route('users.show', $assignee));

        $this->assertNotNull($checkoutAcceptance->refresh()->accepted_at);
        Event::assertDispatched(CheckoutAccepted::class);
    }

    public function test_sign_in_place_acceptance_page_uses_checkout_flow_breadcrumbs()
    {
        $assignee = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();

        $checkoutAcceptance = CheckoutAcceptance::factory()
            ->pending()
            ->for($assignee, 'assignedTo')
            ->for($asset, 'checkoutable')
            ->create();

        $response = $this->actingAs($admin)
            ->withSession([
                'sign_in_place_acceptance_id' => $checkoutAcceptance->id,
                'sign_in_place_item_id' => $asset->id,
                'sign_in_place_resource_type' => 'Assets',
            ])
            ->get(route('account.accept.item', $checkoutAcceptance));

        $response->assertOk()
            ->assertSeeInOrder([
                trans('general.assets'),
                $asset->display_name,
                trans('general.checkout'),
                trans('general.sign_in_place'),
            ], false)
            ->assertSee(route('hardware.index'), false)
            ->assertSee(route('hardware.show', $asset), false)
            ->assertDontSee(route('hardware.checkout.create', $asset), false);
    }
}
