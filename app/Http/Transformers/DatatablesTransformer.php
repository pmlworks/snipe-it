<?php

namespace App\Http\Transformers;

class DatatablesTransformer
{
    /**
     * Transform data for bootstrap tables and API responses for lists of things
     **/
    public function transformDatatables($objects, $total = null)
    {
        $objects_array = [
            'total' => $total ?? count($objects),
            'rows' => $objects,
        ];
        $current_page = app('api_current_page');
        $limit = (int) app('api_limit_value');
        $total_pages = $limit > 0 ? (int) ceil($objects_array['total'] / $limit) : 1;

        $objects_array['current_page'] = $current_page;
        $objects_array['per_page'] = $limit;
        $objects_array['total_pages'] = $total_pages;

        $base_query = collect(request()->query())->except(['page', 'offset'])->all();
        $objects_array['prev_page_url'] = $current_page > 1
            ? request()->url().'?'.http_build_query(array_merge($base_query, ['page' => $current_page - 1, 'limit' => $limit]))
            : null;
        $objects_array['next_page_url'] = $current_page < $total_pages
            ? request()->url().'?'.http_build_query(array_merge($base_query, ['page' => $current_page + 1, 'limit' => $limit]))
            : null;

        return $objects_array;
    }

    /**
     * Transform data for returning the status of items within a bulk action
     **/
    public function transformBulkResponseWithStatusAndObjects($objects, $total)
    {
        $objects_array = [
            'total' => $total ?? count($objects),
            'rows' => $objects,
        ];

        return $objects_array;
    }
}
