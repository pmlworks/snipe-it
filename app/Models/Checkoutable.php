<?php

namespace App\Models;

use App\Helpers\Helper;


class Checkoutable
{
    public function __construct(
        public int $acceptance_id,
        public string $created_at,
        public string $company,
        public string $category,
        public string $model,
        public string $asset_tag,
        public string $name,
        public string $type,
        public object $acceptance,
    ){}

//    public static function fromCheckoutable(Asset|Accessory|etc..)
//    {
//
//    }

    public static function fromAcceptance(CheckoutAcceptance $unaccepted): self
    {
        $unaccepted_row = $unaccepted->checkoutable;
        $acceptance = $unaccepted;

        $company = optional($unaccepted_row->company)->name ?? '';
        $category = $model = $name = $tag = '';
        $type = $acceptance->checkoutable_item_type ?? '';

        if($unaccepted_row instanceof Asset){
            $category = optional($unaccepted_row->model?->category?->present())->nameUrl() ?? '';
            $model = optional($unaccepted_row->present())->modelUrl() ?? '';
            $name = optional($unaccepted_row->present())->nameUrl() ?? '';
            $tag = (string) ($unaccepted_row->asset_tag ?? '');
         }
        elseif($unaccepted_row instanceof Accessory){
            $category = optional($unaccepted_row->category?->present())->nameUrl() ?? '';
            $model = $unaccepted_row->model_number ?? '';
            $name = optional($unaccepted_row->present())->nameUrl() ?? '';
            $tag = '';

        }
        if($unaccepted_row instanceof LicenseSeat){
            $category = '';
            $model = '';
            $name = $unaccepted_row->license->name ?? '';
            $tag = '';
        }
        if($unaccepted_row instanceof Component){
            $category = optional($unaccepted_row->category?->present())->nameUrl() ?? '';
            $model = $unaccepted_row->model_number ?? '';
            $name = $unaccepted_row->present()->nameUrl() ?? '';
            $tag = '';
        }
        return new self(
            acceptance_id: $acceptance->id,
            created_at: Helper::getFormattedDateObject($acceptance->created_at, 'datetime', false),
            company: $company,
            category: $category,
            model: $model,
            asset_tag: $tag,
            name: $name,
            type: $type,
            acceptance: $acceptance,
        );
    }
}
