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

        $acceptance = CheckoutAcceptance::factory()->withoutActionLog()->pending()->create([
            'checkoutable_id' => $accessory->id,
            'checkoutable_type' => Accessory::class,
            'assigned_to_id' => $assignee->id,
        ]);

        Actionlog::factory()->create([
            'action_type' => 'checkout',
            'created_by' => $checkedOutBy->id,
            'target_id' => $assignee->id,
            'item_type' => Accessory::class,
            'item_id' => $accessory->id,
            'created_at' => $acceptance->created_at,
        ]);

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

        $acceptance = CheckoutAcceptance::factory()->withoutActionLog()->pending()->create([
            'checkoutable_id' => $asset->id,
            'checkoutable_type' => Asset::class,
            'assigned_to_id' => $assignee->id,
        ]);

        Actionlog::factory()->create([
            'action_type' => 'checkout',
            'created_by' => $checkedOutBy->id,
            'target_id' => $assignee->id,
            'item_type' => Asset::class,
            'item_id' => $asset->id,
            'created_at' => $acceptance->created_at,
        ]);

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

        $acceptance = CheckoutAcceptance::factory()->withoutActionLog()->pending()->create([
            'checkoutable_id' => $consumable->id,
            'checkoutable_type' => Consumable::class,
            'assigned_to_id' => $assignee->id,
        ]);

        Actionlog::factory()->create([
            'action_type' => 'checkout',
            'created_by' => $checkedOutBy->id,
            'target_id' => $assignee->id,
            'item_type' => Consumable::class,
            'item_id' => $consumable->id,
            'created_at' => $acceptance->created_at,
        ]);

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

        $acceptance = CheckoutAcceptance::factory()->withoutActionLog()->pending()->create([
            'checkoutable_id' => $licenseSeat->id,
            'checkoutable_type' => LicenseSeat::class,
            'assigned_to_id' => $assignee->id,
        ]);

        Actionlog::factory()->create([
            'action_type' => 'checkout',
            'created_by' => $checkedOutBy->id,
            'target_id' => $assignee->id,
            'item_type' => License::class,
            'item_id' => $licenseSeat->license->id,
            'created_at' => $acceptance->created_at,
        ]);

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
}
