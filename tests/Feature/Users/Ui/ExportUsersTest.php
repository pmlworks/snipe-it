<?php

namespace Tests\Feature\Users\Ui;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Group;
use App\Models\LicenseSeat;
use App\Models\User;
use Tests\TestCase;

class ExportUsersTest extends TestCase
{
    public function test_requires_permission()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.export'))
            ->assertForbidden();
    }

    public function test_can_export_users_to_csv()
    {
        $luke = User::factory()
            ->forCompany(['name' => 'Test Company'])
            ->forManager(['first_name' => 'Ben', 'last_name' => 'Kenobi'])
            ->forLocation(['name' => 'Space'])
            ->forDepartment(['name' => 'Lightsaber Fighting Dept'])

            ->create([
                'jobtitle' => 'Jedi Master',
                'employee_num' => '789',
                'first_name' => 'Luke',
                'last_name' => 'Skywalker',
                'username' => 'lskywalker',
                'email' => 'skywalker@jedi.com',
                'notes' => 'Nice guy...',
            ]);

        $groups = Group::factory()
            ->count(2)
            ->sequence(
                ['name' => 'Jedi'],
                ['name' => 'Jedi Dance Crew'],
            )
            ->create();

        $luke->groups()->sync($groups);

        Asset::factory()->assignedToUser($luke)->count(2)->create();
        LicenseSeat::factory()->assignedToUser($luke)->count(2)->create();
        Accessory::factory()->checkedOutToUser($luke)->count(2)->create();
        Consumable::factory()->checkedOutToUser($luke)->count(2)->create();

        $this->actingAs(User::factory()->viewUsers()->create())
            ->get(route('users.export'))
            ->assertOk()
            ->assertCsvHeader()
            ->assertSeeTextInStreamedResponse([
                'Test Company',
                'Jedi Master',
                '789',
                'Luke',
                'Skywalker',
                'Luke Skywalker',
                'lskywalker',
                'skywalker@jedi.com',
                'Ben Kenobi',
                'Space',
                'Lightsaber Fighting Dept',
                '2',
                'Jedi, Jedi Dance Crew',
                trans('general.user'),
                'Nice guy...',
                trans('general.yes'),
            ]);
    }
}
