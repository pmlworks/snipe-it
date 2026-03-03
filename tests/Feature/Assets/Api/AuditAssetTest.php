<?php

namespace Tests\Feature\Assets\Api;

use App\Models\Asset;
use App\Models\User;
use Tests\TestCase;

class AuditAssetTest extends TestCase
{
    public function testPermissionRequiredToBulkAuditAssets()
    {
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.asset.audit', Asset::factory()->create()))
            ->assertForbidden();
    }

    public function testThatANonExistentAssetIdReturnsError()
    {
        $this->actingAsForApi(User::factory()->auditAssets()->create())
            ->postJson(route('api.asset.audit', 123456789))
            ->assertStatusMessageIs('error');
    }

    public function testRequiresPermissionToAuditAsset()
    {
        $asset = Asset::factory()->create();
        $this->actingAsForApi(User::factory()->create())
            ->postJson(route('api.asset.audit', $asset))
            ->assertForbidden();
    }

    public function testLegacyAssetAuditIsSaved()
    {
        $asset = Asset::factory()->create();
        $future = now()->addMonths(5)->toDateString();

        $this->actingAsForApi(User::factory()->auditAssets()->create())
            ->postJson(route('api.asset.audit.legacy'), [
                'asset_tag' => $asset->asset_tag,
                'next_audit_date' => $future,
                'note' => 'test',
            ])
            ->assertStatusMessageIs('success')
            ->assertJson(
                [
                    'messages' =>trans('admin/hardware/message.audit.success'),
                    'payload' => [
                        'id' => $asset->id,
                        'asset_tag' => $asset->asset_tag,
                        'note' => 'test'
                    ],
                ])
            ->assertStatus(200);

        $asset->refresh();
        $this->assertEquals($future, $asset->next_audit_date);
    }

    /**
     * @link https://github.com/grokability/snipe-it/issues/18495
     */
    public function testAuditDoesNotSetNextAuditDateIfGivenNull()
    {
        $this->settings->setAuditInterval(null);

        $asset = Asset::factory()->create(['next_audit_date' => null]);

        $this->actingAsForApi(User::factory()->auditAssets()->create())
            ->postJson(route('api.asset.audit', $asset), [
                'asset_tag' => $asset->asset_tag,
                // this is the important part
                'next_audit_date' => null,
                'note' => null,
            ])
            ->assertStatusMessageIs('success')
            ->assertStatus(200);

        $asset->refresh();
        $this->assertNull($asset->next_audit_date);
    }

    public function testAssetAuditIsSaved()
    {
        $asset = Asset::factory()->create();
        $this->actingAsForApi(User::factory()->auditAssets()->create())
            ->postJson(route('api.asset.audit', $asset), [
                'note' => 'test'
            ])
            ->assertStatusMessageIs('success')
            ->assertJson(
                [
                    'messages' =>trans('admin/hardware/message.audit.success'),
                    'payload' => [
                        'id' => $asset->id,
                        'asset_tag' => $asset->asset_tag,
                        'note' => 'test'
                    ],
                ])
            ->assertStatus(200);
        $this->assertHasTheseActionLogs($asset, ['create', 'audit']);
    }
}
