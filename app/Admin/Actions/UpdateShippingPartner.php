<?php

namespace App\Admin\Actions;

use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\ImportOrder;

class UpdateShippingPartner extends BatchAction
{
    public $name = 'Cập nhật đối tác vận chuyển';

    public function handle(Collection $collection, Request $request)
    {
        $partner = $request->get('shipping_partner');
        $validPartners = [
            'atan' => 'A Tần',
            'other' => 'Khác',
            'oanh' => 'Oanh',
            'nga' => 'Nga',
            'fe' => 'Xuân Phê'
        ];

        if (!array_key_exists($partner, $validPartners)) {
            return $this->response()->error('Đối tác không hợp lệ.')->refresh();
        }

        foreach ($collection as $model) {
            $model->shipping_partner = $partner;
            $model->save();
        }

        $partnerLabel = $validPartners[$partner];
        return $this->response()->success("Đã cập nhật {$collection->count()} phiếu nhập với đối tác '{$partnerLabel}'!")->refresh();
    }

    public function form()
    {
        $this->select('shipping_partner', 'Đối tác vận chuyển mới')->options([
            'atan' => 'A Tần',
            'other' => 'Khác',
            'oanh' => 'Oanh',
            'nga' => 'Nga',
            'fe' => 'Xuân Phê'
        ])->required();
    }
}