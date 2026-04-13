<?php

namespace Tests\Feature\Locations\Ui;

use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class ShowLocationTest extends TestCase
{
    public function test_page_renders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('locations.show', Location::factory()->create()))
            ->assertOk();
    }

    public function test_denies_access_to_regular_user()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('locations.show', Location::factory()->create()))
            ->assertStatus(403)
            ->assertForbidden();
    }

    public function test_denies_print_access_to_regular_user()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('locations.print_all_assigned', Location::factory()->create()))
            ->assertStatus(403)
            ->assertForbidden();
    }

    public function test_page_renders_for_super_admin()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('locations.print_all_assigned', Location::factory()->create()))
            ->assertOk();
    }

    public function test_show_page_includes_parent_location_breadcrumb_hierarchy()
    {
        $grandparent = Location::factory()->create(['name' => 'Grandparent Breadcrumb Location']);
        $parent = Location::factory()->create([
            'name' => 'Parent Breadcrumb Location',
            'parent_id' => $grandparent->id,
        ]);
        $child = Location::factory()->create([
            'name' => 'Child Breadcrumb Location',
            'parent_id' => $parent->id,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('locations.show', $child))
            ->assertOk()
            ->assertSeeInOrder([
                route('locations.show', $grandparent),
                route('locations.show', $parent),
                route('locations.show', $child),
            ]);
    }
}
