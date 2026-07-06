<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BulkAccessoriesController extends Controller
{
    public function destroy(Request $request)
    {
        $this->authorize('delete', Accessory::class);

        $errors = [];
        $success_count = 0;

        foreach ((array) $request->input('ids', []) as $id) {
            $accessory = Accessory::find($id);
            if (is_null($accessory)) {
                $errors[] = trans('admin/accessories/message.does_not_exist', ['id' => $id]);

                continue;
            }

            $accessory->loadCount('checkouts as checkouts_count');
            if (! $accessory->isDeletable()) {
                $errors[] = trans('general.bulk_delete_associations.assoc_checkouts_no_count', [
                    'item_name' => $accessory->name,
                    'item' => trans('general.accessory'),
                ]);

                continue;
            }

            if ($accessory->image) {
                try {
                    Storage::disk('public')->delete('accessories/'.$accessory->image);
                } catch (\Exception $e) {
                    Log::debug($e);
                }
            }

            $accessory->delete();
            $success_count++;
        }

        if (count($errors) > 0) {
            if ($success_count > 0) {
                return redirect()->route('accessories.index')
                    ->with('success', trans_choice('admin/accessories/message.delete.partial_success', $success_count, ['count' => $success_count]))
                    ->with('multi_error_messages', $errors);
            }

            return redirect()->route('accessories.index')->with('multi_error_messages', $errors);
        }

        return redirect()->route('accessories.index')
            ->with('success', trans_choice('admin/accessories/message.delete.bulk_success', $success_count, ['count' => $success_count]));
    }
}
