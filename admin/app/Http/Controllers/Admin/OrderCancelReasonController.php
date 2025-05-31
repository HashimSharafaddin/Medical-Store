<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use App\Models\OrderCancelReason;
use App\Http\Controllers\Controller;
use App\Models\Translation;
use Brian2694\Toastr\Facades\Toastr;

class OrderCancelReasonController extends Controller
{
    public function index()
    {
        $reasons = OrderCancelReason::latest()->paginate(config('default_pagination'));
         $order_typs = BusinessSetting::where('key', 'order_types')->exists();

        return view('admin-views.order.cancelation-reason', compact('reasons','order_typs'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'reason'=>'required|max:255',
            'user_type' =>'required|max:50',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);
        $cancelReason = new OrderCancelReason();
        $cancelReason->reason = $request->reason[array_search('default', $request->lang)];
        $cancelReason->user_type=$request->user_type;
        $cancelReason->created_at = now();
        $cancelReason->updated_at = now();
        $cancelReason->save();
        $data = [];
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if($default_lang == $key && !($request->reason[$index])){
                if ($key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\OrderCancelReason',
                        'translationable_id' => $cancelReason->id,
                        'locale' => $key,
                        'key' => 'reason',
                        'value' => $cancelReason->reason,
                    ));
                }
            }else{
                if ($request->reason[$index] && $key != 'default') {
                    array_push($data, array(
                        'translationable_type' => 'App\Models\OrderCancelReason',
                        'translationable_id' => $cancelReason->id,
                        'locale' => $key,
                        'key' => 'reason',
                        'value' => $request->reason[$index],
                    ));
                }
            }

        }
        Translation::insert($data);
        Toastr::success(translate('messages.order_cancellation_reason_added_successfully'));
        return back();
    }
    public function store2(Request $request)
    {
        // جلب الإعداد الحالي
        $order_typs = BusinessSetting::where('key', 'order_types')->first();
        $lang_array = [];
        $codes = [];
    
        // فك التشفير وفحص القيم
        foreach (json_decode($order_typs?->value, true) ?? [] as $key => $data) {
            if ($data['name'] != $request['names']) {
                // التأكد من وجود الاسم
                if (!isset($data['name'])) {
                    $data['name'] = 'عادي';
                }
    
                array_push($lang_array, $data);
                array_push($codes, $data['name']);
            }
        }
    
        // إضافة الاسم الجديد
        array_push($codes, $request['names']);
        $lang_array[] = [
            'name' => $request['names'],
            'amount' => floatval($request['amount']),
            'status' => 1,
        ];
    
        // تحديث القيمة مرة واحدة فقط
        BusinessSetting::updateOrCreate(
            ['key' => 'order_types'],
            ['value' => json_encode($lang_array)]
        );
    
        Toastr::success('Order type added successfully!');
        return back();
    }
    public function updateOrderType(Request $request)
{
    $order_types_setting = BusinessSetting::where('key', 'order_types')->first();
    $order_types = json_decode($order_types_setting?->value, true) ?? [];

    $updated = false;

    foreach ($order_types as &$type) {
        // ابحث عن العنصر المطلوب بالتحديد بالاسم أو بمعرف مناسب
        if ($type['name'] === $request->old_name) {
            $type['name'] = $request->new_name ?? $type['name'];
            $type['amount'] = $request->amount ?? $type['amount'];
            $type['status'] = $request->status ?? $type['status'];
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        return back()->withErrors(['message' => 'العنصر المطلوب غير موجود.']);
    }

    $order_types_setting->update([
        'value' => json_encode($order_types),
    ]);

    Toastr::success('تم تعديل نوع الطلب بنجاح');
    return back();
}

    public function destroy($cancelReason)
    {
        $cancelReason = OrderCancelReason::findOrFail($cancelReason);
        $cancelReason?->translations()?->delete();
        $cancelReason?->delete();
        Toastr::success(translate('messages.order_cancellation_reason_deleted_successfully'));
        return back();
    }
    public function status2(Request $request, $type, $status)
    {
        BusinessSetting::updateOrInsert(['key' => $type], [
            'value' => $status
        ]);
    
        Toastr::success(translate('messages.status_updated'));
        return back();
    }
    
    public function status(Request $request)
    {
        $cancelReason = OrderCancelReason::findOrFail($request->id);
        $cancelReason->status = $request->status;
        $cancelReason->save();
        Toastr::success(translate('messages.status_updated'));
        return back();
    }
    public function update(Request $request)
    {
        $request->validate([
            'reason' => 'required|max:255',
            'user_type' =>'required|max:50',
            'reason.0' => 'required',
        ],[
            'reason.0.required'=>translate('default_reason_is_required'),
        ]);
        $cancelReason = OrderCancelReason::findOrFail($request->reason_id);
        $cancelReason->reason = $request->reason[array_search('default', $request->lang1)];
        $cancelReason->user_type=$request->user_type;
        $cancelReason?->save();
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang1 as $index => $key) {
            if($default_lang == $key && !($request->reason[$index])){
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\OrderCancelReason',
                            'translationable_id' => $cancelReason->id,
                            'locale' => $key,
                            'key' => 'reason'
                        ],
                        ['value' => $cancelReason->reason]
                    );
                }
            }else{
                if ($request->reason[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'App\Models\OrderCancelReason',
                            'translationable_id' => $cancelReason->id,
                            'locale' => $key,
                            'key' => 'reason'
                        ],
                        ['value' => $request->reason[$index]]
                    );
                }
            }
        }
        Toastr::success(translate('order_cancellation_reason_updated_successfully'));
        return back();
    }
}
