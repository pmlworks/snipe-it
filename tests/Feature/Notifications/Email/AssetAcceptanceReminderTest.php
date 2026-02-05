<?php

namespace Tests\Feature\Notifications\Email;

use App\Mail\CheckoutAccessoryMail;
use App\Mail\CheckoutAssetMail;
use App\Mail\CheckoutConsumableMail;
use App\Mail\CheckoutLicenseMail;
use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\CheckoutAcceptance;
use App\Models\Consumable;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssetAcceptanceReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function testMustHavePermissionToSendReminder()
    {
        $checkoutAcceptance = CheckoutAcceptance::factory()->pending()->create();
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission)
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $checkoutAcceptance->id,
            ]))
            ->assertForbidden();

        Mail::assertNotSent(CheckoutAssetMail::class);
    }

    public function testReminderNotSentIfAcceptanceDoesNotExist()
    {
        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => 999999,
            ]));

        Mail::assertNotSent(CheckoutAssetMail::class);
    }

    public function testReminderNotSentIfAcceptanceAlreadyAccepted()
    {
        $checkoutAcceptanceAlreadyAccepted = CheckoutAcceptance::factory()->accepted()->create();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $checkoutAcceptanceAlreadyAccepted->id,
            ]));

        Mail::assertNotSent(CheckoutAssetMail::class);
    }

    public static function CheckoutAcceptancesToUsersWithoutEmailAddresses()
    {
        yield 'User with null email address' => [
            function () {
                return CheckoutAcceptance::factory()
                    ->pending()
                    ->forAssignedTo(['email' => null])
                    ->create();
            }
        ];

        yield 'User with empty string email address' => [
            function () {
                return CheckoutAcceptance::factory()
                    ->pending()
                    ->forAssignedTo(['email' => ''])
                    ->create();
            }
        ];
    }

    #[DataProvider('CheckoutAcceptancesToUsersWithoutEmailAddresses')]
    public function testUserWithoutEmailAddressHandledGracefully($callback)
    {
        $checkoutAcceptance = $callback();

        $this->actingAs(User::factory()->canViewReports()->create())
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $checkoutAcceptance->id,
            ]))
            // check we didn't crash...
            ->assertRedirect();

        Mail::assertNotSent(CheckoutAssetMail::class);
    }

    public function testReminderIsSentToUserForAccessory()
    {
        $checkedOutBy = User::factory()->canViewReports()->create();

        $assignee = User::factory()->create(['email' => 'test@example.com']);

        $accessory = Accessory::factory()->create();

        $acceptance = $this->createCheckoutAcceptance($accessory, $assignee);

        $this->createActionLogEntry($accessory, $checkedOutBy, $assignee, $acceptance);

        $this->actingAs($checkedOutBy)
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $acceptance->id,
            ]))
            ->assertRedirect(route('reports/unaccepted_assets'));

        Mail::assertSent(CheckoutAccessoryMail::class, 1);
        Mail::assertSent(CheckoutAccessoryMail::class, function (CheckoutAccessoryMail $mail) use ($assignee) {
            return $mail->hasTo($assignee->email);
        });
    }

    public function testReminderIsSentToUserForAsset()
    {
        $checkedOutBy = User::factory()->canViewReports()->create();

        $assignee = User::factory()->create(['email' => 'test@example.com']);

        $asset = Asset::factory()->create();

        $acceptance = $this->createCheckoutAcceptance($asset, $assignee);

        $this->createActionLogEntry($asset, $checkedOutBy, $assignee, $acceptance);

        $this->actingAs($checkedOutBy)
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $acceptance->id,
            ]))
            ->assertRedirect(route('reports/unaccepted_assets'));

        Mail::assertSent(CheckoutAssetMail::class, 1);
        Mail::assertSent(CheckoutAssetMail::class, function (CheckoutAssetMail $mail) use ($assignee) {
            return $mail->hasTo($assignee->email);
        });
    }

    public function testReminderIsSentToUserForConsumable()
    {
        $checkedOutBy = User::factory()->canViewReports()->create();

        $assignee = User::factory()->create(['email' => 'test@example.com']);

        $consumable = Consumable::factory()->create();

        $acceptance = $this->createCheckoutAcceptance($consumable, $assignee);

        $this->createActionLogEntry($consumable, $checkedOutBy, $assignee, $acceptance);

        $this->actingAs($checkedOutBy)
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $acceptance->id,
            ]))
            ->assertRedirect(route('reports/unaccepted_assets'));

        Mail::assertSent(CheckoutConsumableMail::class, 1);
        Mail::assertSent(CheckoutConsumableMail::class, function (CheckoutConsumableMail $mail) use ($assignee) {
            return $mail->hasTo($assignee->email);
        });
    }

    public function testReminderIsSentToUserForLicenseSeat()
    {
        $checkedOutBy = User::factory()->canViewReports()->create();

        $assignee = User::factory()->create(['email' => 'test@example.com']);

        $licenseSeat = LicenseSeat::factory()->create();

        $acceptance = $this->createCheckoutAcceptance($licenseSeat, $assignee);

        $this->createActionLogEntry($licenseSeat, $checkedOutBy, $assignee, $acceptance);

        $this->actingAs($checkedOutBy)
            ->post(route('reports/unaccepted_assets_sent_reminder', [
                'acceptance_id' => $acceptance->id,
            ]))
            ->assertRedirect(route('reports/unaccepted_assets'));

        Mail::assertSent(CheckoutLicenseMail::class, 1);
        Mail::assertSent(CheckoutLicenseMail::class, function (CheckoutLicenseMail $mail) use ($assignee) {
            return $mail->hasTo($assignee->email);
        });
    }

    private function createCheckoutAcceptance(Model $item, Model $assignee): CheckoutAcceptance
    {
        return CheckoutAcceptance::factory()
            ->for($item, 'checkoutable')
            ->for($assignee, 'assignedTo')
            ->withoutActionLog()
            ->pending()
            ->create();
    }

    private function createActionLogEntry(Model $item, Model $admin, Model $assignee, CheckoutAcceptance $acceptance): Actionlog
    {
        $itemId = $item->id;
        $itemType = get_class($item);

        if (get_class($item) === LicenseSeat::class) {
            $itemId = $item->license->id;
            $itemType = License::class;
        }

        return Actionlog::factory()
            ->for($admin, 'adminuser')
            ->for($assignee, 'target')
            // ->for($item, 'item')
            ->create([
                'action_type' => 'checkout',
                'item_id' => $itemId,
                'item_type' => $itemType,
                'created_at' => $acceptance->created_at,
            ]);
    }
}
