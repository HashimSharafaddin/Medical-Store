<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AdComments;
use App\Models\Advertisement;
use App\Models\Cart;
use App\Models\Item;
use App\Models\PharmacyItemDetails;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ItemCampaign;
use Illuminate\Support\Facades\Validator;

use function PHPSTORM_META\type;

class CartController extends Controller
{
    public function get_carts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }

    public function add_to_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
            'item_id' => 'required|integer',
            'model' => 'required|string|in:Item,ItemCampaign',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $model = $request->model === 'Item' ? 'App\Models\Item' : 'App\Models\ItemCampaign';
        $item = $request->model === 'Item' ? Item::find($request->item_id) : ItemCampaign::find($request->item_id);

        $cart = Cart::where('item_id', $request->item_id)->where('item_type', $model)->where('variation', json_encode($request->variation))->where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->first();

        if ($cart) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item', 'message' => translate('messages.Item_already_exists')]
                ]
            ], 403);
        }

        if ($item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->with('item')->get();

        //        foreach($carts as $cart){
        //                if($cart?->item?->store_id  && $cart?->item?->store_id != $item->store_id){
        //                    return response()->json([
        //                        'errors' => [
        //                            ['code' => 'different_stores', 'message' => translate('messages.Please_select_items_from_the_same_store')]
        //                        ]
        //                    ], 403);
        //                }
        //            }


        $cart = new Cart();
        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->item_id = $request->item_id;
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = isset($request->add_on_ids) ? json_encode($request->add_on_ids) : json_encode([]);
        $cart->add_on_qtys = isset($request->add_on_qtys) ? json_encode($request->add_on_qtys) : json_encode([]);
        $cart->item_type = $request->model;
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation) ? json_encode($request->variation) : json_encode([]);
        $cart->to_cart = $request->to_cart;

        $cart->save();

        $item->carts()->save($cart);

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }

    public function update_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $cart = Cart::find($request->cart_id);
        $item = $cart->item_type === 'App\Models\Item' ? Item::find($cart->item_id) : ItemCampaign::find($cart->item_id);
        if ($item->maximum_cart_quantity && ($request->quantity > $item->maximum_cart_quantity)) {
            return response()->json([
                'errors' => [
                    ['code' => 'cart_item_limit', 'message' => translate('messages.maximum_cart_quantity_exceeded')]
                ]
            ], 403);
        }

        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = isset($request->add_on_ids) ? json_encode($request->add_on_ids) : $cart->add_on_ids;
        $cart->add_on_qtys = isset($request->add_on_qtys) ? json_encode($request->add_on_qtys) : $cart->add_on_qtys;
        $cart->price = $request->price;
        $cart->quantity = $request->quantity;
        $cart->variation = isset($request->variation) ? json_encode($request->variation) : $cart->variation;
        $cart->save();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }

    public function remove_cart_item(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $cart = Cart::find($request->cart_id);
        $cart->delete();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }
    public function add_note(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $cart = Cart::find($request->cart_id);
        $select = $cart->select;
        // if ($select == 0) {
            $cart->note = $request->note??'';
        // } else {
        //     $cart->select = 0;
        // }
        $cart->save();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }
    public function add_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'type' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
        $item = new Item;
        $item->name = $request->name;
$unit =  Unit::where('unit', 'like', '%'.$request->type.'%')->first();
        
        $item->food_variations = json_encode([]);
        $item->variations = json_encode([]);
        $item->price = 0;
        $item->image =  'store_logo.png' ;
        $item->available_time_starts =  '00:00:00';
        $item->available_time_ends =  '23:59:59';
        $item->discount = 0;
        $item->discount_type = 'percent';
        $item->unit_id = $unit->id??1;
        $item->attributes = json_encode([]);
        $item->add_ons = json_encode([]);
        $item->store_id =  0;
        $item->maximum_cart_quantity = 100;
        $item->veg = $request->veg??0;
        $item->expiry_date = $request->expiry_date??"2027-07-01 00:00:00";
        $item->category_ids = json_encode([["id" => 11, "position" => 0]]);

        $item->category_id = 0;
        $item->description =  '';
        $item->choice_options = json_encode([]);
        $item->stock = 100 ;
        $item->store_ids = json_encode(['0']);
        $item->module_id =$request->header('moduleId');
        $item->status = 1;
        $item->from_user_app = 1;

        // $item->expiry_date = $request->expiry_date;
        $item->user_type =0;
        $item->company_name = "";
        // $item->generic_name = $request->generic_name??"";
        $item->save();
        $item_details = new PharmacyItemDetails();
        $item_details->item_id = $item->id;
        $item_details->common_condition_id = $request->condition_id??0;
        $item_details->is_basic =  0;
        $item_details->is_prescription_required = 0;
        $item_details->save();
        // $item->generic()->sync($generic_ids);
     $cart = new Cart();
        $cart->user_id = $user_id;
        $cart->module_id = $request->header('moduleId');
        $cart->item_id = $item->id;
        $cart->is_guest = $is_guest;
        $cart->add_on_ids = json_encode([]);
        $cart->add_on_qtys = json_encode([]);
        $cart->item_type = 'App\Models\Item';
        $cart->price = $request->price??0;
        $cart->quantity =intval( $request->qty)??1;
        $cart->variation = json_encode([]);
        $cart->to_cart = 1;
        $cart->from_user = 1;

        $cart->save();

        $item->carts()->save($cart);
                // $select = $cart->select;
        // if ($select == 0) {
            // $cart->note = $request->note??'';
        // } else {
        //     $cart->select = 0;
        // }
        $cart->save();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }
    public function selectItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $cart = Cart::find($request->cart_id);
        $select = $cart->select;
        if ($select == 0) {
            $cart->select = 1;
        } else {
            $cart->select = 0;
        }
        $cart->save();

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }
    public function add_like(Request $request)
    {
        // ✅ التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:advertisements,id',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
    
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;
    
        // ✅ جلب المنشور
        $post = Advertisement::find($request->post_id);
    
        // ✅ جلب الإعجابات الحالية
        $likes = json_decode($post->likes, true) ?? [];
    
        // ✅ التحقق مما إذا كان المستخدم قد أُعجب مسبقًا
        $userLiked = collect($likes)->contains(function ($like) use ($user_id) {
            return $like['user_id'] == $user_id;
        });
    
        if (!$userLiked) {
            $likes[] = [
                'user_id' => $user_id,
                'like_id' => $request->post_id,
            ];
        } else {
            // ✅ إزالة الإعجاب إذا كان المستخدم قد أعجب بالفعل (إلغاء الإعجاب)
            $likes = array_filter($likes, function ($like) use ($user_id) {
                return $like['user_id'] != $user_id;
            });
        }
    
        // ✅ حفظ الإعجابات
        $post->likes = json_encode(array_values($likes)); // إعادة فهرسة المصفوفة
        $post->save();
    
        return response()->json([
            'message' => $userLiked ? 'Like removed successfully.' : 'Like added successfully.',
            'likes' => $likes,
        ], 200);
    }
    
    public function selectAll(Request $request)
    {
        // ✅ 1. التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'select' => 'required',
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // ✅ 2. تحديد معرف المستخدم ونوعه (ضيف أم مستخدم مسجل)
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        // ✅ 3. تحديث الحقول دفعة واحدة (Bulk Update)
        Cart::where('user_id', $user_id)
            ->where('is_guest', $is_guest)
            ->where('module_id', $request->header('moduleId'))
            ->update(['select' => $request->select]);

        // ✅ 4. إعادة البيانات بعد التحديث
        $carts = Cart::where('user_id', $user_id)
            ->where('is_guest', $is_guest)
            ->where('module_id', $request->header('moduleId'))
            ->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });

        // ✅ 5. إرجاع الاستجابة
        return response()->json($carts, 200);
    }
    public function sendToCart(Request $request)
    {
        // ✅ 1. التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        // ✅ 2. تحديد معرف المستخدم ونوعه (ضيف أم مستخدم مسجل)
        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        // ✅ 3. تحديث الحقول دفعة واحدة (Bulk Update)
        Cart::where('user_id', $user_id)
            ->where('select', 1)
            ->where('is_guest', $is_guest)
            ->where('module_id', $request->header('moduleId'))
            ->update(['to_cart' => 1]);

        // ✅ 4. إعادة البيانات بعد التحديث
        $carts = Cart::where('user_id', $user_id)
            ->where('is_guest', $is_guest)
            ->where('module_id', $request->header('moduleId'))
            ->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });

        // ✅ 5. إرجاع الاستجابة
        return response()->json($carts, 200);
    }
    public function remove_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_id' => $request->user ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user_id = $request->user ? $request->user->id : $request['guest_id'];
        $is_guest = $request->user ? 0 : 1;

        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->where('to_cart', 1)->get();

        foreach ($carts as $cart) {
            $cart->delete();
        }


        $carts = Cart::where('user_id', $user_id)->where('is_guest', $is_guest)->where('module_id', $request->header('moduleId'))->get()
            ->map(function ($data) {
                $data->add_on_ids = json_decode($data->add_on_ids, true);
                $data->add_on_qtys = json_decode($data->add_on_qtys, true);
                $data->variation = json_decode($data->variation, true);
                $data->item = Helpers::cart_product_data_formatting(
                    $data->item,
                    $data->variation,
                    $data->add_on_ids,
                    $data->add_on_qtys,
                    false,
                    app()->getLocale()
                );
                return $data;
            });
        return response()->json($carts, 200);
    }
    public function add_comment(Request $request)
    {

        // $validator = Validator::make($request->all(), [
        //     'comment' => 'required',
        //     'post_id' => 'required',

        // ]);
        try {
            $adds = new AdComments();
            $adds->ad_id = $request->post_id;
            $adds->titel = $request->comment;
            $adds->user_id = $request->user->id ?? 0;
            $adds->user_name = $request->user->f_name ?? 0;

            $adds->save();
        } catch (\Exception $e) {
            return response()->json([
                'message' => translate('messages.failed')
            ], 400);
        }
        return response()->json([
            'message' => translate('messages.ok'),
            'comment' => $adds->titel,
        ], 200);
    }
}
