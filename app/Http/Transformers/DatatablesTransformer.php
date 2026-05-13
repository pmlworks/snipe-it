<?php

namespace App\Http\Transformers;

class DatatablesTransformer
{
    /**
     * Transform data for bootstrap tables and API responses for lists of things
     **/
    public function transformDatatables($objects, $total = null)
    {
        (isset($total)) ? $objects_array['total'] = $total : $objects_array['total'] = count($objects);
        $objects_array['rows'] = $objects;
        $objects_array['current_page'] = app('api_current_page');
        $limit = app('api_limit_value');
        $objects_array['per_page'] = $limit;
        $objects_array['total_pages'] = $limit > 0 ? (int) ceil($objects_array['total'] / $limit) : 1;

        return $objects_array;
    }

    /**
     * Transform data for returning the status of items within a bulk action
     **/
    public function transformBulkResponseWithStatusAndObjects($objects, $total)
    {
        (isset($total)) ? $objects_array['total'] = $total : $objects_array['total'] = count($objects);
        $objects_array['rows'] = $objects;

        return $objects_array;
    }
}
