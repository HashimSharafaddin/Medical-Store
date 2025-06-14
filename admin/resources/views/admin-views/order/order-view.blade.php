@extends('layouts.admin.app')

@section('title', translate('Order Details'))

@section('content')
    <?php
    $deliverman_tips = 0;
    $campaign_order = isset($order?->details[0]?->item_campaign_id )  ? true : false;
    $reasons=\App\Models\OrderCancelReason::where('status', 1)->where('user_type' ,'admin' )->get();
    $parcel_order = $order->order_type == 'parcel' ? true : false;
    $tax_included =0;
    $max_processing_time = $order->store?explode('-', $order->store['delivery_time'])[0]:0;
    ?>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/shopping-basket.png') }}" class="w--20"
                                alt="">
                        </span>
                        <span>
                            {{ translate('order_details') }} <span
                                class="badge badge-soft-dark rounded-circle ml-1">{{ $order->details->count() }}</span>
                        </span>
                    </h1>
                </div>

                <div class="col-sm-auto">
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle mr-1"
                        href="{{ route('admin.order.details', [$order['id'] - 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="{{ translate('Previous order') }}">
                        <i class="tio-chevron-left"></i>
                    </a>
                    <a class="btn-icon btn-sm btn-soft-secondary rounded-circle"
                        href="{{ route('admin.order.details', [$order['id'] + 1]) }}" data-toggle="tooltip"
                        data-placement="top" title="{{ translate('Next order') }}">
                        <i class="tio-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Page Header -->

        @php
            $refund_amount = $order->order_amount - $order->delivery_charge - $order->dm_tips;
        @endphp
        <div class="row flex-xl-nowrap" id="printableArea">
            <div class="col-lg-8 order-print-area-left">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header border-0 align-items-start flex-wrap">
                        <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                            <div>
                                <h1 class="page-header-title d-flex align-items-center __gap-5px">
                                    {{ translate('messages.order') }} #{{ $order['id'] }}
                                    @if ($campaign_order)
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.campaign_order') }}
                                        </span>
                                    @endif
                                    @if ($order->edited)
                                        <span class="badge badge-soft-dark ml-sm-3">
                                            {{ translate('messages.edited') }}
                                        </span>
                                    @endif
                                </h1>
                                <span class="mt-2 d-block d-flex align-items-center __gap-5px">
                                    <i class="tio-date-range"></i>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                                </span>
                                @if (!$parcel_order)
                                    <h6 class="mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        <i class="tio-shop"></i>
                                        <span>{{ translate('messages.store') }}</span> <span>:</span> <span
                                            class="badge badge-soft-primary">{{ Str::limit($order->store ? $order->store->name : translate('messages.store deleted!'), 25, '...') }}</span>
                                    </h6>
                                @endif
                                @if ($order->schedule_at && $order->scheduled)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px">
                                        <span>{{ translate('messages.scheduled_at') }}</span>
                                        <span>:</span> <label
                                            class="fz--10 badge badge-soft-warning">{{ date('d M Y ' . config('timeformat'), strtotime($order['schedule_at'])) }}</label>
                                    </h6>
                                @endif
                                @if ($order->coupon)
                                    <h6 class="text-capitalize d-flex align-items-center __gap-5px"><span>{{ translate('messages.coupon') }}</span>
                                        <span>:</span> <label class="fz--10 badge badge-soft-primary">{{ $order->coupon_code }}
                                            ({{ translate('messages.' . $order->coupon->coupon_type) }})</label>
                                    </h6>
                                @endif
                                <div class="hs-unfold mt-1">
                                    <h5>
                                        <button
                                            class="btn order--details-btn-sm btn--primary btn-outline-primary btn--sm font-regular d-flex align-items-center __gap-5px"
                                            data-toggle="modal" data-target="#locationModal"><i class="tio-poi"></i>
                                            {{ translate('messages.show_locations_on_map') }}</button>
                                    </h5>
                                </div>
                                @if($order['cancellation_reason'])
                                    <h6 class="text-capitalize my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.Cancelled_By') }} :</span>
                                        {{ $order['canceled_by'] }}
                                    </h6>
                                    <h6 class=" my-2 ml-2">
                                        <span class="text-danger">{{ translate('messages.order_cancellation_reason') }} :</span>
                                        {{ $order['cancellation_reason'] }}
                                    </h6>
                                @endif
                                @if ($order['unavailable_item_note'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_unavailable_item_note') }} :
                                        </span>
                                        {{ $order['unavailable_item_note'] }}
                                    </h6>
                                @endif
                                @if ($order['delivery_instruction'])
                                    <h6 class="w-100 badge-soft-warning">
                                        <span class="text-dark">
                                            {{ translate('messages.order_delivery_instruction') }} :
                                        </span>
                                        {{ $order['delivery_instruction'] }}
                                    </h6>
                                @endif
                                @if ($order['order_note'])
                                    <h6>
                                        {{ translate('messages.order_note') }} :
                                        {{ $order['order_note'] }}
                                    </h6>
                                @endif
                                @if($order?->offline_payments && $order?->offline_payments->status == 'denied' && $order?->offline_payments->note )
                                <h6 class="w-100 badge-soft-warning">
                                    <span class="text-dark">
                                        {{ translate('messages.Offline_payment_rejection_note') }} :
                                    </span>
                                        {{  $order?->offline_payments->note }}
                                    </h6>
                                @endif
                            </div>
                           
                            {{-- <div class="d-sm-none">
                                <a class="btn btn--primary print--btn font-regular d-flex align-items-center __gap-5px"
                                    href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                            </div> --}}
                        </div>
                        <div class="order-invoice-right mt-3 mt-sm-0">
                            <div class="btn--container ml-auto align-items-center justify-content-end">

                                @if (  !$parcel_order &&  !$editing && in_array($order->order_status, ['pending', 'confirmed', 'processing', 'accepted']) &&
                                        isset($order->store) && !$campaign_order &&
                                        $order->prescription_order == 0 && count($order?->payments) == 0 && $order?->ref_bonus_amount == 0 && $order?->flash_admin_discount_amount == 0 && ($order->payment_method == 'cash_on_delivery'))
                                    <button class="btn btn-sm btn--danger btn-outline-danger font-regular edit-order" type="button">
                                        <i class="tio-edit"></i> {{ translate('messages.edit') }}
                                    </button>
                                @endif
                                <a class="btn btn--primary print--btn font-regular d-none d-sm-block"
                                    href={{ route('admin.order.generate-invoice', [$order['id']]) }}>
                                    <i class="tio-print mr-sm-1"></i> <span>{{ translate('messages.print_invoice') }}</span>
                                </a>
                                <div class="btn--container ml-auto align-items-center justify-content-end">
                                    <a class="btn btn--primary print--btn font-regular d-none d-sm-block"
                                      onclick="printCard()">
    
                                        <i class="tio-print mr-sm-1"></i> <span>{{ _('طباعة فاتوره A4') }}</span>
                                    </a>
                                </div>
                            </div>
                            <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                <h6>
                                    <span>{{ translate('status') }}</span> <span>:</span>
                                    @if ($order['order_status'] == 'pending')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.pending') }}
                                        </span>
                                    @elseif($order['order_status'] == 'confirmed')
                                        <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.confirmed') }}
                                        </span>
                                    @elseif($order['order_status'] == 'processing')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.processing') }}
                                        </span>
                                    @elseif($order['order_status'] == 'accepted')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.accepted') }}
                                        </span>
                                        @elseif($order['order_status'] == 'in_review')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.in_review') }}
                                        </span>
                                    @elseif($order['order_status'] == 'picked_up')
                                        <span class="badge badge-soft-warning ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.out_for_delivery') }}
                                        </span>
                                    @elseif($order['order_status'] == 'delivered')
                                        <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.delivered') }}
                                        </span>
                                    @elseif($order['order_status'] == 'failed')
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                            {{ translate(str_replace('_', ' ', $order['order_status'])) }}
                                        </span>
                                    @endif
                                </h6>
                                <h6 class="text-capitalize">
                                    <span>{{ translate('messages.payment_method') }}</span> <span>:</span>
                                    <span>{{ translate(str_replace('_', ' ', $order['payment_method'])) }}</span>
                                </h6>

                                    <!-- offline_payment -->
                                    @if($order?->offline_payments)
                                    <span>{{ translate('Payment_verification') }}</span> <span>:</span>
                                        @if ($order?->offline_payments->status == 'pending')
                                            <span class="badge badge-soft-info ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.pending') }}
                                            </span>
                                        @elseif ($order?->offline_payments->status == 'verified')
                                            <span class="badge badge-soft-success ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.verified') }}
                                            </span>
                                        @elseif ($order?->offline_payments->status == 'denied')
                                            <span class="badge badge-soft-danger ml-2 ml-sm-3 text-capitalize">
                                                {{ translate('messages.denied') }}
                                            </span>
                                    @endif

                                    @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                        @if ($key != 'method_id')
                                        <h6 class="">
                                            <div class="d-flex justify-content-sm-end text-capitalize">
                                                <span class="title-color">{{translate($key)}} :</span>
                                                <strong>{{ $item }}</strong>
                                            </div>
                                        </h6>
                                        @endif
                                    @endforeach
                                @endif

                                <h6 class="">
                                    @if ($order['transaction_reference'] == null)
                                        <span>{{ translate('messages.reference_code') }}</span> <span>:</span>
                                        <button class="btn btn-outline-primary btn-sm" data-toggle="modal"
                                            data-target=".bd-example-modal-sm">
                                            {{ translate('messages.add') }}
                                        </button>
                                    @else
                                        <span>{{ translate('messages.reference_code') }}</span> <span>:</span>
                                        <span>{{ $order['transaction_reference'] }}</span>
                                    @endif
                                </h6>

                                <h6 class="text-capitalize">
                                    <span>{{ translate('Order Type') }}</span>
                                    <span>:</span> <label
                                        class="fz--10 badge badge-soft-primary m-0">{{ translate(str_replace('_', ' ', $order['order_type'])) }}</label>
                                </h6>
                                <h6>
                                    <span>{{ translate('payment_status') }}</span> <span>:</span>
                                    @if ($order['payment_status'] == 'paid')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.paid') }}
                                        </span>
                                        @elseif ($order['payment_status'] == 'partially_paid')

                                        @if ($order->payments()->where('payment_status','unpaid')->exists())
                                        <strong class="text-danger">{{ translate('messages.partially_paid') }}</strong>
                                        @else
                                        <strong class="text-success">{{ translate('messages.paid') }}</strong>
                                        @endif
                                    @else
                                        <strong class="text-danger">{{ translate('messages.unpaid') }}</strong>
                                    @endif

                                </h6>
                                @if ($order->store && $order->store->module->module_type == 'food')
                                <h6>
                                    <span>{{ translate('cutlery') }}</span> <span>:</span>
                                    @if ($order['cutlery'] == '1')
                                        <span class="badge badge-soft-success ml-sm-3">
                                            {{ translate('messages.yes') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger ml-sm-3">
                                            {{ translate('messages.no') }}
                                        </span>
                                    @endif

                                </h6>
                                @endif
                                @if ($order->order_attachment)
                                        @php
                                            $order_images = json_decode($order->order_attachment,true);
                                        @endphp
                                        <h5 class="text-dark">
                                            <span>{{ translate('messages.prescription') }}</span> <span>:</span>
                                        </h5>
                                        <div class="d-flex flex-wrap flex-md-row-reverse" style="gap:15px">
                                            @foreach ($order_images as $key => $item)
                                            @php($item = is_array($item)?$item:['img'=>$item,'storage'=>'public'])
                                                <div>
                                                    <button class="btn w-100 px-0" data-toggle="modal"
                                                        data-target="#imagemodal{{ $key }}"
                                                        title="{{ translate('messages.order_attachment') }}">
                                                        <div class="gallary-card ml-auto">
                                                            <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                                alt="{{ translate('messages.prescription') }}"
                                                                class="initial--22 object-cover">
                                                        </div>
                                                    </button>
                                                </div>
                                                <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                                    role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h4 class="modal-title" id="myModalLabel">
                                                                    {{ translate('messages.prescription') }}</h4>
                                                                <button type="button" class="close"
                                                                    data-dismiss="modal"><span
                                                                        aria-hidden="true">&times;</span><span
                                                                        class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <img  src="{{\App\CentralLogics\Helpers::get_full_url('order', $item['img'], $item['storage']??'public') }}"
                                                                    class="initial--22 w-100">
                                                            </div>
                                                            @php($storage = $item['storage']??'public')
                                                            @php($file = $storage == 's3'?base64_encode('order/' . $item['img']):base64_encode('public/order/' . $item['img']))
                                                            <div class="modal-footer">
                                                                <a class="btn btn-primary"
                                                                    href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                        class="tio-download"></i>
                                                                    {{ translate('messages.download') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body px-0">
                        <!-- item cart -->
                        @if ($editing && !$campaign_order)
                        <hr>
                            <div class="row  px-4 py-5">
                                <div class="col-12">
                                    <div class="row justify-content-end">
                                        <div class="col-sm-6">
                                            <form id="search-form">
                                                <!-- Search -->
                                                <div class="input-group input--group">
                                                    <input id="datatableSearch" type="search"
                                                        value="{{ $keyword ? $keyword : '' }}" name="search"
                                                        class="form-control h--45px" placeholder="Search here"
                                                        aria-label="Search here">
                                                    <button class="btn btn--secondary h--45px"><i
                                                            class="tio-search"></i></button>
                                                </div>
                                                <!-- End Search -->
                                            </form>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="input-group header-item w-100">
                                                <select name="category" id="category"
                                                    class="form-control js-select2-custom mx-1 set-category-filter"
                                                    title="{{ translate('messages.select_category') }}">
                                                    <option value="">{{ translate('messages.all_categories') }}
                                                    </option>
                                                    @foreach ($categories as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $category == $item->id ? 'selected' : '' }}>
                                                            {{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-5" id="items">
                                    <div class="row g-3 mb-auto justify-content-center">
                                        @foreach ($products as $product)
                                            <div class="order--item-box item-box">
                                                @include('admin-views.order.partials._single_product', [
                                                    'product' => $product,
                                                    'store_data' => $order->store,
                                                ])
                                                {{-- <hr class="d-sm-none"> --}}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-12">
                                    {!! $products->withQueryString()->links() !!}
                                </div>
                            </div>
                        @endif

                        @if ($order->order_type == 'parcel')
                            <?php
                            $coupon = null;
                            $total_addon_price = 0;
                            $product_price = 0;
                            $store_discount_amount = 0;
                            $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                            $ref_bonus_amount = $order['ref_bonus_amount'];
                            $extra_packaging_amount = $order['extra_packaging_amount'];
                            $store_flash_discount_amount = $order['flash_store_discount_amount'];
                            $del_c = $order['delivery_charge'];
                            $additional_charge = $order['additional_charge'];
                            $total_tax_amount = 0;
                            $total_addon_price = 0;
                            $coupon_discount_amount = 0;
                            $deliverman_tips = $order['dm_tips'];
                            ?>
                            <div class="mx-3">
                                <div class="media align-items-center cart--media pb-2">
                                    <div class="avatar avatar-xl mr-3"
                                        title="{{ $order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found') }}">
                                        <img class="img-fluid onerror-image"
                                        src="{{ $order->parcel_category?->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                    </div>
                                    <div class="media-body">
                                        <div class="row">
                                            @if ($order->add_items != null)
                                            {{-- <strong>اصناف غير متوفره في التطبيق</strong> --}}
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>{{ _('الاسم') }}</th>
                                                            <th>{{ _('الكميه') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach (json_decode($order->add_items, true) as $key => $item)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td> <!-- رقم الصف -->
                                                                <td>{{ $item['note'] }}</td> <!-- اسم المنتج -->
                                                                <td>{{ $item['qty'] }}</td> <!-- الكمية -->
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                            {{-- <div class="col-md-6 mb-3 mb-md-0">
                                                <strong>
                                                    {{ Str::limit($order->parcel_category ? $order->parcel_category->name : translate('messages.parcel_category_not_found'), 25, '...') }}</strong><br>
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $order->parcel_category ? $order->parcel_category->description : translate('messages.parcel_category_not_found') }}</span>
                                                </div>
                                            </div>

                                            <div class="col col-md-2 align-self-center">
                                                <h6>{{ translate('messages.distance') }}</h6>
                                                <span>{{ $order->distance }} {{ translate('km') }}</span>
                                            </div>
                                            <div class="col col-md-1 align-self-center">
                                                <h6>{{ _('الكميه') }}</h6>

                                                    <div class="font-size-sm text-body">
                                                        <span>{{ $order->qty ??"" }}</span>
                                                    </div>
                                            </div>

                                            <div class="col col-md-3 align-self-center text-right">
                                                <h6>{{ translate('messages.delivery_charge') }}</h6>
                                                <span>{{ \App\CentralLogics\Helpers::format_currency($del_c) }}</span>
                                            </div> --}}
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-2">
                            </div>
                        @else
                            <?php
                            $coupon = null;
                            $total_addon_price = 0;
                            $product_price = 0;
                            if ($order->prescription_order == 1) {
                                $product_price = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] - $order['additional_charge'] + $order['store_discount_amount'];
                                if($order->tax_status == 'included'){
                                    $product_price += $order['total_tax_amount'];
                                }
                            }
                            $store_discount_amount = 0;
                            $admin_flash_discount_amount = $order['flash_admin_discount_amount'];
                            $ref_bonus_amount = $order['ref_bonus_amount'];
                            $extra_packaging_amount = $order['extra_packaging_amount'];
                            $store_flash_discount_amount = $order['flash_store_discount_amount'];
                            $additional_charge = $order['additional_charge'];
                            $del_c = $order['delivery_charge'];
                            if ($editing) {
                                $del_c = $order['original_delivery_charge'];
                            }
                            if ($order->coupon_code) {
                                $coupon = \App\Models\Coupon::where(['code' => $order['coupon_code']])->first();
                                if ($editing && $coupon->coupon_type == 'free_delivery') {
                                    $del_c = 0;
                                    $coupon = null;
                                }
                            }
                            $details = $order->details;
                            if ($editing) {
                                $details = session('order_cart');
                            } else {
                                foreach ($details as $key => $item) {
                                    $details[$key]->status = true;
                                }
                            }
                            ?>
                                    <div class="table1" id="printableArea">
                                        
                            <div class="table-responsive">
                                <table
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-0">{{ translate('messages.#') }}</th>
                                            <th class="border-0">{{ translate('messages.item_details') }}</th>
                                            <th class="border-0">{{ translate('messages.quantity') }}</th>

                                            <th class="text-right  border-0">{{ _('الصلاحية') }}</th>

                                            @if ($order->store && $order->store->module->module_type == 'food')
                                                <th class="border-0">{{ translate('messages.addons') }}</th>
                                            @endif

                                            <th class="text-right  border-0">{{ translate('messages.store_name') }}</th>
<th></th>
                                            <th class="text-right  border-0">{{ translate('messages.price') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($details as $key => $detail)
                                            @if (isset($detail->item_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->item = json_decode($detail->item_details, true);
                                                }
                                                $store = \App\Models\Store::where(['id' => $detail->store_id])->first();

                                                    $product = \App\Models\Item::where(['id' => $detail->item['id']])->first();
                                                ?>

                                                <tr>
                                                    <td>
                                                        <!-- Static Count Number -->
                                                        <div>
                                                            {{ $key + 1 }}
                                                        </div>
                                                        <!-- Static Count Number -->
                                                    </td>
                                                    <td>
                                                        <div class="media media--sm">
                                                            @if ($editing)
                                                                <div class="avatar avatar-xl mr-3 cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                    title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                    <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                    src="{{ $product->image_full_url ??''}}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                        alt="Image Description">
                                                                </div>
                                                            @else
                                                                <a class="avatar avatar-xl mr-3"
                                                                    href="{{ route('admin.item.view', [$detail->item['id'],'module_id' => $order->module_id]) }}">
                                                                    <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                                    src="{{ $product->image_full_url??'' }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}"
                                                                        alt="Image Description">
                                                                </a>
                                                            @endif
                                                            <div class="media-body">
                                                                <div>
                                                                    <strong class="line--limit-1">
                                                                        {{ $detail->item['name'] }}</strong>
                                                                    <h6>
                                                                        {{ $detail->item['unit']['unit'] }}
                                                                        {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                    </h6>
                                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                                        @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                            @foreach (json_decode($detail['variation'], true) as $variation)
                                                                                @if (isset($variation['name']) && isset($variation['values']))
                                                                                    <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                    @foreach ($variation['values'] as $value)
                                                                                        <span
                                                                                            class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                    @endforeach
                                                                                @else
                                                                                    @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                        <strong><u>
                                                                                                {{ translate('messages.Variation') }}
                                                                                                : </u></strong>
                                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                            <div
                                                                                                class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                                <span
                                                                                                    class="font-weight-bold">{{ $variation }}</span>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @endif
                                                                                    {{-- @break --}}
                                                                                @endif
                                                                            @endforeach
                                                                        @endif
                                                                    @else
                                                                        @if (count(json_decode($detail['variation'], true)) > 0)
                                                                            <strong><u>{{ translate('messages.variation') }}
                                                                                    :
                                                                                </u></strong>
                                                                            @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                    <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                        <span
                                                                                            class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                    </div>
                                                                                @endif
                                                                            @endforeach
                                                                        @endif
                                                                    @endif

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><input class="form-control  item-qty-input"   id="item_qty_{{ $detail['id'] }}" type="number"value="{{ $detail['quantity'] }}" data-id="{{ $detail['id'] }}">
                                                        @if($detail['item']['from_user_app'] ==0)
                                                        <div class="label--container ml-auto align-items-center justify-content-end">
                  <a class="label label--danger success font-regular d-none d-sm-block"
                      >
                      <i class="tio-success mr-sm-1"></i> <span>{{ _('') }}</span>
                  </a>
              </div>
              @else
                                                        <div class="label--container ml-auto align-items-center justify-content-end">
                  <a class="label label--danger de font-regular d-none d-sm-block"
                      >
                      <i class="tio-danger mr-sm-1"></i> <span style="color: red">{{ _('صنف غير متوفر ') }}</span>
                  </a>
              @endif

                                                    </td>
                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                        <td>
                                                            <div>
                                                                @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                    @if ($key2 == 0)
                                                                        <strong><u>{{ translate('messages.addons') }} :
                                                                            </u></strong>
                                                                    @endif
                                                                    <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                        <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                    </div>
                                                                    @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td>{{ Str::limit($detail->item['expiry_date'],15)}}</td>
                                                    <td>
                                                        <span>الاسم :{{$store->name??"" }}
                                                            -</span>
                                                            <span class="font-weight-bold">
                                                             رقم الفاورة :    {{ $detail['order_store_number'] }} 
                                                            </span>                                                        
                                                    {{-- </td>                                                    <td>
                                                        @if($detail['item']['from_user_app'] ==0)
                                                        <div class="label--container ml-auto align-items-center justify-content-end">
                  <a class="label label--danger success font-regular d-none d-sm-block"
                      >
                      <i class="tio-success mr-sm-1"></i> <span>{{ _('') }}</span>
                  </a>
              </div>
              @else
                                                        <div class="label--container ml-auto align-items-center justify-content-end">
                  <a class="label label--danger de font-regular d-none d-sm-block"
                      >
                      <i class="tio-danger mr-sm-1"></i> <span style="color: red">{{ _('صنف غير متوفر ') }}</span>
                  </a>
              @endif

                                                    </td> --}}
                                                    <td class="text-right">
                                                        <div>
                                                            @php($amount = $detail['price'] * $detail['quantity'])
                                                            <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                            </h5>
                                                        </div>
                                                    </td>
                                                </tr>

                                                @php($product_price += $amount)
                                                @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                                <!-- End Media -->
                                            @elseif(isset($detail->item_campaign_id) && $detail->status)
                                                <?php
                                                if (!$editing) {
                                                    $detail->campaign = json_decode($detail->item_details, true);
                                                }
                                                    $campaign = \App\Models\ItemCampaign::where(['id' => $detail->campaign['id']])->first();
                                                    $store = \App\Models\Store::where(['id' => $detail->store_id])->first();

                                                ?>
                                                <tr>
                                                    <td>
                                                        <!-- Static Count Number -->
                                                        <div>
                                                            {{ $key + 1 }}
                                                        </div>
                                                        <!-- Static Count Number -->
                                                    </td>
                                                    <td>
                                                        <div class="media media--sm">
                                                            @if ($editing)
                                                                <div class="avatar avatar-xl mr-3  cursor-pointer quick-view-cart-item" data-key="{{ $key }}"
                                                                    title="{{ translate('messages.click_to_edit_this_item') }}">
                                                                    <span
                                                                        class="avatar-status avatar-lg-status avatar-status-dark"><i
                                                                            class="tio-edit"></i></span>
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="Image Description">
                                                                </div>
                                                            @else
                                                                <a class="avatar avatar-xl mr-3"
                                                                    href="{{ route('admin.campaign.view', ['item', $detail->campaign['id']]) }}">
                                                                    <img class="img-fluid rounded onerror-image"
                                                                        src="{{ $campaign?->image_full_url ?? asset('public/assets/admin/img/900x400/img1.jpg') }}"
                                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                                        alt="Image Description">
                                                                </a>
                                                            @endif

                                                            <div class="media-body">
                                                                <div>
                                                                    <strong
                                                                        class="line--limit-1">{{ Str::limit($detail->campaign['name'], 20, '...') }}</strong>

                                                                    <h6>
                                                                        {{ $detail['quantity'] }} x
                                                                        {{ \App\CentralLogics\Helpers::format_currency($detail['price']) }}
                                                                    </h6>
                                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                                        @if (isset($detail['variation']) ? json_decode($detail['variation'], true) : [])
                                                                            @foreach (json_decode($detail['variation'], true) as $variation)
                                                                                @if (isset($variation['name']) && isset($variation['values']))
                                                                                    <span class="d-block text-capitalize">
                                                                                        <strong>
                                                                                            {{ $variation['name'] }} -
                                                                                        </strong>
                                                                                    </span>
                                                                                    @foreach ($variation['values'] as $value)
                                                                                        <span
                                                                                            class="d-block text-capitalize">
                                                                                            &nbsp; &nbsp;
                                                                                            {{ $value['label'] }} :
                                                                                            <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                                        </span>
                                                                                    @endforeach
                                                                                @else
                                                                                    @if (isset(json_decode($detail['variation'], true)[0]))
                                                                                        <strong><u>
                                                                                                {{ translate('messages.Variation') }}
                                                                                                : </u></strong>
                                                                                        @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                            <div
                                                                                                class="font-size-sm text-body">
                                                                                                <span>{{ $key1 }}
                                                                                                    : </span>
                                                                                                <span
                                                                                                    class="font-weight-bold">{{ $variation }}</span>
                                                                                            </div>
                                                                                        @endforeach
                                                                                    @endif
                                                                                    {{-- @break --}}
                                                                                @endif
                                                                            @endforeach
                                                                        @endif
                                                                    @else
                                                                        @if (count(json_decode($detail['variation'], true)) > 0)
                                                                            <strong><u>{{ translate('messages.variation') }}
                                                                                    :</u></strong>
                                                                            @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                                @if ($key1 != 'stock' || ($order->store && config('module.' . $order->store->module->module_type)['stock']))
                                                                                    <div class="font-size-sm text-body">
                                                                                        <span>{{ $key1 }} :
                                                                                        </span>
                                                                                        <span
                                                                                            class="font-weight-bold">{{ Str::limit($variation, 15, '...') }}</span>
                                                                                    </div>
                                                                                @endif
                                                                            @endforeach
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                        <td>
                                                            <div>
                                                                @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                                    @if ($key2 == 0)
                                                                        <strong><u>{{ translate('messages.addons') }} :
                                                                            </u></strong>
                                                                    @endif
                                                                    <div class="font-size-sm text-body">
                                                                        <span>{{ Str::limit($addon['name'], 20, '...') }}
                                                                            : </span>
                                                                        <span class="font-weight-bold">
                                                                            {{ $addon['quantity'] }} x
                                                                            {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                                        </span>
                                                                    </div>
                                                                    @php($total_addon_price += $addon['price'] * $addon['quantity'])
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    @endif
                                                    <td>
                                                        <span>{{$store->name??"" }}
                                                            : </span>
                                                            <span class="font-weight-bold">
                                                                {{ $detail['order_store_number'] }} x
                                                                {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                            </span>                                                        
                                                    </td>
                                                    <td class="text-right">
                                                        <div>
                                                            @php($amount = $detail['price'] * $detail['quantity'])
                                                            <h5>{{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                                            </h5>
                                                        </div>
                                                    </td>
                                                </tr>

                                                @php($product_price += $amount)
                                                @php($store_discount_amount += $detail['discount_on_item'] * $detail['quantity'])
                                                <!-- End Media -->
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                                {{-- @if ($order->add_items != null)
                                <strong>اصناف غير متوفره في التطبيق</strong>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>{{ _('الاسم') }}</th>
                                                <th>{{ _('العبوه') }}</th>
                                                <th>{{ _('الكميه') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (json_decode($order->add_items, true) as $key => $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td> <!-- رقم الصف -->
                                                    <td>{{ $item['name']??'' }}</td> <!-- اسم المنتج -->
                                                    <td>{{ $item['package']??'' }}</td> <!-- العبوة -->
                                                    <td>{{ $item['qty']??0 }}</td> <!-- الكمية -->
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif --}}
                            
                            </div>
                        </div>
                            <?php
                            $coupon_discount_amount = $order['coupon_discount_amount'];

                            $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount - $admin_flash_discount_amount - $ref_bonus_amount - $store_flash_discount_amount - $extra_packaging_amount;

                            $total_tax_amount = $order['total_tax_amount'];
                            if($order->tax_status == 'included'){
                                $total_tax_amount=0;
                            }
                            $deliverman_tips = $order['dm_tips'];

                            if ($editing) {
                                $store_discount = \App\CentralLogics\Helpers::get_store_discount($order->store);
                                if (isset($store_discount)) {
                                    if ($product_price + $total_addon_price < $store_discount['min_purchase']) {
                                        $store_discount_amount = 0;
                                    }

                                    if ($store_discount_amount > $store_discount['max_discount'] && $store_discount_amount > $store_discount['max_discount']) {
                                        $store_discount_amount = $store_discount['max_discount'];
                                    }
                                }
                                $coupon_discount_amount = $coupon ? \App\CentralLogics\CouponLogic::get_discount($coupon, $product_price + $total_addon_price - $store_discount_amount ) : $order['coupon_discount_amount'];
                                $tax = $order->store->tax;

                                $total_price = $product_price + $total_addon_price - $store_discount_amount - $coupon_discount_amount;

                                $total_tax_amount = $tax > 0 ? ($total_price * $tax) / 100 : 0;

                                $total_tax_amount = round($total_tax_amount, 2);

                                $tax_included = \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first() ?  \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first()->value : 0;
                                if ($tax_included ==  1){
                                    $total_tax_amount=0;
                                }

                                $store_discount_amount = round($store_discount_amount, 2);

                                if ($order?->store?->free_delivery) {
                                    $del_c = 0;
                                }

                                $free_delivery_over = \App\Models\BusinessSetting::where('key', 'free_delivery_over')->first()->value;
                                if (isset($free_delivery_over)) {
                                    if ($free_delivery_over <= $product_price + $total_addon_price - $coupon_discount_amount - $store_discount_amount) {
                                        $del_c = 0;
                                    }
                                }
                                if ($order->order_type == 'take_away') {
                                    $del_c = 0;
                                }
                            } else {
                                $store_discount_amount = $order['store_discount_amount'];
                            }

                            ?>
                        @endif
                        <div class="mx-3">
                            <hr>
                        </div>
                        <div class="row justify-content-md-end mb-3 mt-4 mx-0">
                            <div class="col-md-9 col-lg-8">
                                <dl class="row text-right">
                                    @if (!$parcel_order)
                                        <dt class="col-6">{{ translate('messages.items_price') }}:</dt>
                                        <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($product_price) }}</dd>
                                        @if ($order->store && $order->store->module->module_type == 'food')
                                            <dt class="col-6">{{ translate('messages.addon_cost') }}:</dt>
                                            <dd class="col-6">
                                                {{ \App\CentralLogics\Helpers::format_currency($total_addon_price) }}
                                                <hr>
                                            </dd>
                                        @endif

                                        <dt class="col-6">{{ translate('messages.subtotal') }}
                                            @if ($order->tax_status == 'included' ||  $tax_included ==  1)
                                            ({{ translate('messages.TAX_Included') }})
                                            @endif
                                            :</dt>
                                        <dd class="col-6">
                                            {{ \App\CentralLogics\Helpers::format_currency($product_price + $total_addon_price) }}
                                        </dd>
                                        <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                                        <dd class="col-6">
                                            - {{ \App\CentralLogics\Helpers::format_currency($store_discount_amount + $admin_flash_discount_amount  + $store_flash_discount_amount) }}
                                        </dd>



                                        <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                                        <dd class="col-6">
                                            - {{ \App\CentralLogics\Helpers::format_currency($coupon_discount_amount) }}
                                        </dd>
                                        @if ($ref_bonus_amount > 0)
                                        <dt class="col-6">{{ translate('messages.Referral_Discount') }}:</dt>
                                        <dd class="col-6">
                                            - {{ \App\CentralLogics\Helpers::format_currency($ref_bonus_amount) }}
                                        </dd>
                                        @endif
                                        @if ($order->tax_status == 'excluded' || $order->tax_status == null  )
                                        {{-- @php($tax_a=0) --}}
                                        <dt class="col-6">{{ translate('messages.vat/tax') }}:</dt>
                                        <dd class="col-6 text-right">
                                            +
                                            {{ \App\CentralLogics\Helpers::format_currency($total_tax_amount) }}
                                        </dd>
                                        @endif
                                        <dt class="col-6">{{ translate('messages.delivery_fee') }}:</dt>
                                        <dd class="col-6">
                                            + {{ \App\CentralLogics\Helpers::format_currency($del_c) }}
                                            <hr>
                                        </dd>
                                    @endif

                                    <dt class="col-6">{{ translate('messages.delivery_man_tips') }}</dt>
                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($deliverman_tips) }}</dd>
                                    <dt class="col-6">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??\App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}</dt>

                                    <dd class="col-6">
                                        + {{ \App\CentralLogics\Helpers::format_currency($additional_charge) }}</dd>

                                        @if ($extra_packaging_amount > 0)
                                        <dt class="col-6">{{ translate('messages.Extra_Packaging_Amount') }}:</dt>
                                        <dd class="col-6">
                                            + {{ \App\CentralLogics\Helpers::format_currency($extra_packaging_amount) }}
                                        </dd>
                                        @endif

                                    <dt class="col-6">{{ translate('messages.total') }}:</dt>
                                    <dd class="col-6">

                                        {{ \App\CentralLogics\Helpers::format_currency($product_price + $del_c + $total_tax_amount + $total_addon_price + $deliverman_tips + $additional_charge - $coupon_discount_amount - $store_discount_amount - $admin_flash_discount_amount - $store_flash_discount_amount - $ref_bonus_amount +$extra_packaging_amount )  }}
                                    </dd>
                                    @if ($order?->payments)
                                        @foreach ($order?->payments as $payment)
                                            @if ($payment->payment_status == 'paid')
                                                @if ( $payment->payment_method == 'cash_on_delivery')

                                                <dt class="col-6">{{ translate('messages.Paid_with_Cash') }} ({{  translate('COD')}}) :</dt>
                                                @else

                                                <dt class="col-6">{{ translate('messages.Paid_by') }} {{  translate($payment->payment_method)}} :</dt>
                                                @endif
                                            @else

                                            <dt class="col-6">{{ translate('Due_Amount') }} ({{  $payment->payment_method == 'cash_on_delivery' ?  translate('messages.COD') : translate($payment->payment_method) }}) :</dt>
                                            @endif
                                        <dd class="col-6 text-right">
                                            {{ \App\CentralLogics\Helpers::format_currency($payment->amount) }}
                                        </dd>
                                        @endforeach
                                    @endif
                                </dl>
                                <!-- End Row -->
                            </div>
                            @if ($editing)
                                <div class="col-12">
                                    <div class="btn--container justify-content-end">
                                        <button class="btn btn-sm btn--reset cancel-edit-order" type="button" >{{ translate('messages.cancel') }}</button>
                                        <button class="btn btn-sm btn--primary submit-edit-order" type="button">{{ translate('messages.submit') }}</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <!-- End Row -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>

            <div class="col-lg-4 order-print-area-right">
                @if ($order->order_status == 'canceled')

                    <div class="card mb-3">


                        <div class="card-body pt-2">

                            <ul class="delivery--information-single mt-3">
                                <li>
                                    <span class=" badge badge-soft-danger "> {{ translate('messages.Cancel_Reason') }} :</span>
                                    <span class="info">  {{ $order->cancellation_reason }} </span>
                                </li>
                                <hr class="w-100">
                                <li>
                                    <span class="name">{{ translate('Cancel_Note') }} </span>
                                    <span class="info">  {{ $order->cancellation_note ?? translate('messages.N/A')}} </span>
                                </li>
                                <li>
                                    <span class="name">{{ translate('Canceled_By') }} </span>
                                    <span class="info">  {{ translate($order->canceled_by) }} </span>
                                </li>
                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid' )
                                        @if ( $order?->payments)
                                            @php( $pay_infos =$order->payments()->where('payment_status','paid')->get())
                                            @foreach ($pay_infos as $pay_info)
                                                <li>
                                                    <span class="name">{{ translate('Amount_paid_by') }} {{ translate($pay_info->payment_method) }} </span>
                                                    <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($pay_info->amount)  }} </span>
                                                </li>
                                            @endforeach
                                        @else
                                        <li>
                                            <span class="name">{{ translate('Amount_paid_by') }} {{ translate($order->payment_method) }} </span>
                                            <span class="info ">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                                        </li>
                                        @endif
                                @endif

                                @if ($order->payment_status == 'paid' || $order->payment_status == 'partially_paid')
                                    @if ( $order?->payments)
                                        @php( $amount =$order->payments()->where('payment_status','paid')->sum('amount'))
                                            <li>
                                                <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                                <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($amount)  }} </span>
                                            </li>
                                    @else
                                    <li>
                                        <span class="name">{{ translate('Amount_Returned_To_Wallet') }} </span>
                                        <span class="info">  {{ \App\CentralLogics\Helpers::format_currency($order->order_amount)  }} </span>
                                    </li>
                                    @endif
                                @endif


                            </ul>
                        </div>
                    </div>

                @endif
                @php($refund = \App\Models\BusinessSetting::where(['key' => 'refund_active_status'])->first())

                @if (!empty($order->refund))
                    @if (
                        $order->order_status == 'refund_requested' ||
                            $order->order_status == 'refunded' ||
                            $order->order_status == 'refund_request_canceled')
                        <div class="card mb-2">
                            <div class="card-header border-0 d-block text-center pb-0">
                                <h4 class="m-0">{{ translate('messages.Refund Request') }} </h4>
                                <span>
                                    {{ date('d M Y ' . config('timeformat'), strtotime($order->refund->created_at)) }}
                                </span>

                                @if ($order->order_status == 'refund_requested')
                                    <span
                                        class="badge __badge badge-primary __badge-abs">{{ translate('messages.pending') }}</span>
                                @elseif($order->order_status == 'refunded')
                                    <span
                                        class="badge __badge badge-info __badge-abs">{{ translate('messages.refunded') }}</span>
                                @elseif($order->refund->order_status == 'refund_request_canceled')
                                    <span
                                        class="badge __badge-pill badge-danger __badge-abs">{{ translate('messages.rejected') }}</span>
                                @endif

                            </div>
                            <div class="card-body pt-2">
                                <label class="input-label"
                                    for="exampleFormControlInput1">{{ translate('messages.image') }} : </label>
                                <div class="row g-3">
                                    @php($data = isset($order->refund->image) ? json_decode($order->refund->image, true) : 0)
                                    @if ($data)
                                        @foreach ($data as $key => $img)
                                        @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                            <div class="col-3">
                                                <img class="img__aspect-1 rounded border w-100 onerror-image" data-toggle="modal"
                                                    data-target="#imagemodal{{ $key }}"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                    src="{{ \App\CentralLogics\Helpers::get_full_url('refund',$img['img'],$img['storage']) }}">
                                            </div>
                                            <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                                role="dialog" aria-labelledby="myModalLabel{{ $key }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h4 class="modal-title"
                                                                id="myModalLabel{{ $key }}">
                                                                {{ translate('Refund Image') }}</h4>
                                                            <button type="button" class="close"
                                                                data-dismiss="modal"><span
                                                                    aria-hidden="true">&times;</span><span
                                                                    class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <img
                                                            src="{{ \App\CentralLogics\Helpers::get_full_url('refund',$img['img'],$img['storage']) }}"

                                                                class="initial--22 w-100">
                                                        </div>
                                                        @php($storage = $img['storage']??'public')
                                                        @php($file = $storage == 's3'?base64_encode('refund/' . $img['img']):base64_encode('public/refund/' . $img['img']))
                                                        <div class="modal-footer">
                                                            <a class="btn btn-primary"
                                                                href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                                    class="tio-download"></i>
                                                                {{ translate('messages.download') }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="col-3">
                                            <img class="img__aspect-1 rounded border w-100 onerror-image"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                        </div>
                                    @endif
                                </div>
                                <hr>


                                <ul class="delivery--information-single mt-3">
                                    <li>
                                        <span class="name">{{ translate('Reason') }} </span>
                                        <span class="info"> {{ $order->refund->customer_reason }} </span>
                                    </li>
                                    <li>
                                        <span class="name">{{ translate('amount') }} </span>
                                        <span class="info"> {{ $order->refund->refund_amount }}</span>
                                    </li>
                                    <li>
                                        <span class="name">{{ translate('Method') }} </span>
                                        <span class="info"> {{ $order->refund->refund_method }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Status') }} </span>
                                        <span class="info"> {{ $order->refund->refund_status }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Admin Note') }} </span>
                                        <span class="info"> {{ $order->refund->admin_note ?? 'No Note' }}</span>
                                    </li>
                                    <li>
                                        <span class="name"> {{ translate('Customer Note') }} </span>
                                        <span class="info"> {{ $order->refund->customer_note ?? 'No Note' }}</span>
                                    </li>
                                    <hr class="w-100">
                                </ul>
                                @if ($order->store)
                                <div class="btn--container refund--btn">
                                    @if (
                                        (($refund && $refund->value == true) || $order->order_status == 'refund_requested') &&
                                            $order->payment_status == 'paid' &&
                                            $order->order_status != 'refunded')
                                        <button class="btn btn--primary btn--sm route-alert"
                                                data-url="{{ route('admin.order.status', ['id' => $order['id'],'order_status' => 'refunded',
                                            ]) }}" data-message="{{ translate('messages.you_want_to_refund_this_order', ['amount' => $refund_amount . ' ' . \App\CentralLogics\Helpers::currency_code()]) }}" data-title="{{ translate('messages.are_you_sure_want_to_refund') }}"
                                        ><i
                                                class="tio-money"></i> <span
                                                class="ml-1">{{ translate('messages.Refund') }}</span> </button>
                                    @endif
                                    @if ($order->order_status == 'refund_requested' )
                                        <button type="button" class="btn btn--danger btn-outline-danger"
                                            data-toggle="modal" data-target="#refund_cancelation_note">
                                            <i class="tio-money"></i> <span
                                                class="ml-1">{{ translate('messages.Cancel Refund') }}</span> </button>
                                    @endif
                                </div>

                                @endif
                            </div>
                        </div>
                    @endif
                @endif
                @if ( !in_array($order->order_status, ['refund_requested', 'refunded', 'refund_request_canceled', 'delivered','canceled']) )
                    <div class="card">
                        <div class="card-header justify-content-center">
                            <h5 class="card-title">{{ translate('order_setup') }}</h5>
                        </div>
                        <div class="card-body">



                            @if($order?->offline_payments  && !in_array($order->order_status, ['canceled']) )
                                <div class="card border-info text-center mb-2">
                                    <div class="card-body">
                                        <h2>
                                            {{ $order?->offline_payments->status == 'verified'?translate('Payment_Verified'):translate('Payment_Verification') }}
                                        </h2>
                                        @if ($order?->offline_payments->status == 'pending')
                                            <p class="text-danger"> {{ translate('Please_Verify_the_payment_before_confirm_order.') }}</p>
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Verify_Payment') }}</button>
                                            </div>

                                        @elseif($order?->offline_payments->status == 'verified')
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Payment_Details') }}</button>
                                            </div>
                                        @elseif($order?->offline_payments->status == 'denied')
                                            <div class="btn--container justify-content-center">
                                                <button  type="button" class="btn btn--primary btn-sm" data-toggle="modal" data-target="#verifyViewModal" >{{ translate('messages.Recheck_Verification') }}</button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif


                            @if ($order->offline_payments == null  || ($order?->offline_payments && $order?->offline_payments->status == 'verified'))
                            @if ( !in_array($order->order_status, [ 'refunded', 'refund_request_canceled']))
                                <div class="hs-unfold w-100">
                                    <div class="dropdown">
                                        <button
                                            class="form-control h--45px dropdown-toggle d-flex justify-content-between align-items-center w-100"
                                            type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                            aria-haspopup="true" aria-expanded="false">
                                            <?php
                                            $message= match($order['order_status']){
                                                            'pending' => translate('messages.pending'),
                                                            'confirmed' => translate('messages.confirmed'),
                                                            'accepted' => translate('messages.accepted'),
                                                            'processing' => translate('messages.processing'),
                                                            'in_review' => translate('messages.in_review'),

                                                            'handover' => translate('messages.handover'),
                                                            'picked_up' => translate('messages.out_for_delivery'),
                                                            'delivered' => translate('messages.delivered'),
                                                            'canceled' => translate('messages.canceled'),
                                                            default => translate('messages.status') ,
                                                        };
                                        ?>
                                        {{ $message }}
                                        </button>
                                        @php($order_delivery_verification = (bool) \App\Models\BusinessSetting::where(['key' => 'order_delivery_verification'])->first()->value)
                                        <div class="dropdown-menu text-capitalize" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item {{ $order['order_status'] == 'pending' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'pending']) }}" data-message="{{ translate('Change status to pending ?') }}"
                                                    href="javascript:">{{ translate('messages.pending') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'confirmed' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'confirmed']) }}" data-message="{{ translate('Change status to confirmed ?') }}"
                                                    href="javascript:">{{ translate('messages.confirmed') }}</a>
                                                    <a class="dropdown-item {{ $order['order_status'] == 'in_review' ? 'active' : '' }} route-alert"
                                                    data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'in_review']) }}" data-message="{{ translate('Change status to in_review ?') }}"
                                                     href="javascript:">{{ translate('messages.in_review') }}</a>
                                                @if ($order->order_type != 'parcel')
                                                    @if ($order->store && $order->store->module->module_type == 'food')
                                                    <a class="dropdown-item {{ $order['order_status'] == 'processing' ? 'active' : '' }} order_status_change_alert" data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}" data-message="{{ translate('Change status to cooking ?') }}" data-processing={{ $max_processing_time }}
                                                        href="javascript:">{{ translate('messages.processing') }}</a>
                                                    @else
                                                    <a class="dropdown-item {{ $order['order_status'] == 'processing' ? 'active' : '' }} route-alert"
                                                       data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'processing']) }}" data-message="{{ translate('Change status to processing ?') }}"
                                                        href="javascript:">{{ translate('messages.processing') }}</a>
                                                    @endif
                                                    <a class="dropdown-item {{ $order['order_status'] == 'handover' ? 'active' : '' }} route-alert"
                                                       data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'handover']) }}" data-message="{{ translate('Change status to handover ?') }}"
                                                        href="javascript:">{{ translate('messages.handover') }}</a>
                                                @endif
                                                <a class="dropdown-item {{ $order['order_status'] == 'picked_up' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'picked_up']) }}" data-message="{{ translate('Change status to out for delivery ?') }}"
                                                    href="javascript:">{{ translate('messages.out_for_delivery') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'delivered' ? 'active' : '' }} route-alert"
                                                   data-url="{{ route('admin.order.status', ['id' => $order['id'], 'order_status' => 'delivered']) }}" data-message="{{ translate('Change status to delivered (payment status will be paid if not)?') }}"
                                                    href="javascript:">{{ translate('messages.delivered') }}</a>
                                                <a class="dropdown-item {{ $order['order_status'] == 'canceled' ? 'active' : '' }} canceled-status">{{ translate('messages.canceled') }}</a>
                                            </div>

                                    </div>
                                </div>
                            @endif
                                @if (!in_array($order->order_status, [ 'refunded','delivered', 'canceled']) &&  ( !$order->delivery_man && $order['order_type'] != 'take_away' && (($order->store && !$order?->store?->sub_self_delivery) || $parcel_order)))
                                    <div class="w-100 text-center mt-3">
                                        <button type="button" class="btn btn--primary w-100" data-toggle="modal"
                                            data-target="#myModal" data-lat='21.03' data-lng='105.85'>
                                            {{ translate('messages.assign_delivery_man_manually') }}
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
                @if ($parcel_order || ($order['order_type'] != 'take_away' && $order->store ))
                    @if ($order->delivery_man)
                        <div class="card mt-2">
                            <div class="card-body">
                                <h5 class="card-title mb-3 d-flex flex-wrap align-items-center">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                    <span>{{ translate('messages.deliveryman') }}</span>


                                    @if ($order?->store?->sub_self_delivery)
                                       &nbsp; ({{ translate('messages.store') }})
                                    @endif

                                    @if (!isset($order->delivered) && !$order?->store?->sub_self_delivery)
                                        <a type="button" href="#myModal" class="text--base cursor-pointer ml-auto"
                                            data-toggle="modal" data-target="#myModal">
                                            {{ translate('messages.change') }}
                                        </a>
                                    @endif
                                </h5>
                                <a class="media align-items-center deco-none customer--information-single"
                                    href="{{ !$order?->store?->sub_self_delivery ?  route('admin.users.delivery-man.preview', [$order->delivery_man['id']]) : '#' }}">
                                    <div class="avatar avatar-circle">
                                        <img class="avatar-img onerror-image"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            src="{{ $order->delivery_man?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                            alt="Image Description">
                                    </div>
                                    <div class="media-body">
                                        <span
                                            class="text-body d-block text-hover-primary mb-1">{{ $order->delivery_man['f_name'] . ' ' . $order->delivery_man['l_name'] }}</span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-shopping-basket-outlined mr-2"></i>
                                            {{ $order->delivery_man->orders_count }}
                                            {{ translate('messages.orders_delivered') }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-call-talking-quiet mr-2"></i>
                                            {{ $order->delivery_man['phone'] }}
                                        </span>

                                        <span class="text--title font-semibold d-flex align-items-center">
                                            <i class="tio-email-outlined mr-2"></i>
                                            {{ $order->delivery_man['email'] }}
                                        </span>

                                    </div>
                                </a>
                                <hr>
                                @php($address = $order->dm_last_location)
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5>{{ translate('messages.last_location') }}</h5>
                                </div>
                                @if (isset($address))
                                    <span class="d-block">
                                        <a target="_blank"
                                            href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                            <i class="tio-map"></i> {{ $address['location'] }}<br>
                                        </a>
                                    </span>
                                @else
                                    <span class="d-block text-lowercase qcont">
                                        {{ translate('messages.location_not_found') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif


                <div class="card mt-2">
                    <div class="card-body pt-3">
                        @if ($order->customer && $order->is_guest == 0)
                            <h5 class="card-title mb-3">
                                <span class="card-header-icon">
                                    <i class="tio-user"></i>
                                </span>
                                <span>{{ translate('customer_information') }}</span>
                            </h5>

                            <a class="media align-items-center deco-none customer--information-single"
                                href="{{ route('admin.users.customer.view', [$order->customer['id']]) }}">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img onerror-image"
                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        src="{{ $order->customer->image_full_url }}"
                                        alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="fz--14px text--title font-semibold text-hover-primary d-block">
                                        {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                                    </span>
                                    <span>{{ $order->customer->orders_count }} {{ translate('messages.orders') }}</span>
                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-call-talking-quiet mr-2"></i> <span>{{ $order->customer['phone'] }}</span>
                                    </span>
                                    <span class="text--title d-flex align-items-center">
                                        <i class="tio-email mr-2"></i> <span>{{ $order->customer['email'] }}</span>
                                    </span>
                                </div>
                            </a>


                        @elseif($order->is_guest)
                            <span class="badge badge-soft-success py-2 d-block qcont">
                                {{ translate('Guest_user') }}
                            </span>

                        @else
                            <span class="badge badge-soft-danger py-2 d-block qcont">
                                {{ translate('Customer Not found!') }}
                            </span>
                        @endif
                            @if ($order->receiver_details)
                                @php($receiver_details = $order->receiver_details)
                                <h5 class="card-title mt-3">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                    <span>{{ translate('messages.receiver_info') }}</span>
                                </h5>
                                @if (isset($receiver_details))
                                    <span class="delivery--information-single mt-3">
                                        <span class="name">{{ translate('messages.name') }}</span>
                                        <span class="info">{{ $receiver_details['contact_person_name'] }}</span>
                                        <span class="name">{{ translate('messages.contact') }}</span>
                                        <a class="deco-none info d-flex"
                                           href="tel:{{ $receiver_details['contact_person_number'] }}">
                                            {{ $receiver_details['contact_person_number'] }}</a>

                                                @if (data_get($receiver_details,'floor') != '')
                                            <span class="name">{{ translate('Floor') }}</span> <span
                                                class="info">{{ data_get($receiver_details,'floor', translate('messages.N/A'))  }}</span>
                                        @endif
                                        @if ( data_get($receiver_details,'house') != '')
                                            <span class="name">{{ translate('House') }}</span> <span
                                                class="info">{{data_get($receiver_details,'house', translate('messages.N/A')) }}</span>
                                        @endif
                                        @if ( data_get($receiver_details,'road') != '')
                                            <span class="name">{{ translate('Road') }}</span> <span
                                                class="info">{{ data_get($receiver_details,'road', translate('messages.N/A')) }}</span>
                                        @endif

                                        <hr class="w-100">

                                        @if (isset($receiver_details['address']))
                                            @if (isset($receiver_details['latitude']) && isset($receiver_details['longitude']))
                                                <a class="mt-2 d-flex" target="_blank"
                                                   href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $receiver_details['latitude'] }}+{{ $receiver_details['longitude'] }}">
                                                    <i class="tio-poi"></i>{{ $receiver_details['address'] }}
                                                </a>
                                            @else
                                                <i class="tio-poi"></i>{{ $receiver_details['address'] }}
                                            @endif
                                        @endif
                                    </span>
                                @endif
                            @endif

                        @if ($order->delivery_address)
                            @php($address = json_decode($order->delivery_address, true))
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">
                                    <span class="card-header-icon">
                                        <i class="tio-user"></i>
                                    </span>
                                    <span>{{ translate($parcel_order ? 'messages.sender' : 'messages.delivery_info') }}</span>
                                </h5>
                                @if ($order->order_status != 'delivered' && $order['partially_paid_amount'] == 0)
                                    @if (isset($address) && !$parcel_order)
                                        <a class="link d-flex" data-toggle="modal" data-target="#shipping-address-modal"
                                            href="javascript:"><i class="tio-edit"></i></a>
                                    @endif
                                @endif
                            </div>
                            @if (isset($address))
                                <div class="delivery--information-single mt-3">
                                    <span class="name">{{ translate('messages.name') }}</span>
                                    <span class="info">{{ data_get($address,'contact_person_name', translate('messages.N/A')) }}</span>
                                    <span class="name">{{ translate('messages.contact') }}</span>
                                    <a class="deco-none info" href="tel:{{ data_get($address,'contact_person_number', translate('messages.N/A'))  }}">
                                        {{ data_get($address,'contact_person_number', translate('messages.N/A')) }}</a>

                                        @if (data_get($address,'floor') != '')
                                            <span class="name">{{ translate('Floor') }}</span> <span
                                            class="info">{{ data_get($address,'floor', translate('messages.N/A'))  }}</span>
                                            @endif
                                        @if ( data_get($address,'road') != '')
                                        <span class="name">{{ translate('Road') }}</span> <span
                                        class="info">{{ data_get($address,'road', translate('messages.N/A')) }}</span>
                                            @endif
                                        @if ( data_get($address,'house') != '')
                                        <span class="name">{{ translate('House') }}</span> <span
                                        class="info">{{data_get($address,'house', translate('messages.N/A')) }}</span>
                                            @endif

                                    <hr class="w-100">
                                    <div>
                                        @if (isset($address['address']))
                                            @if ( data_get($address,'latitude', null) &&  data_get($address,'longitude', null))
                                                <a target="_blank" class="d-flex align-items-center"
                                                    href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $address['latitude'] }}+{{ $address['longitude'] }}">
                                                    <i class="tio-poi"></i>{{ $address['address'] }}
                                                </a>
                                            @else
                                                <i class="tio-poi"></i>{{ $address['address'] }}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <!-- Customer Card -->
                @php($data = isset($order->order_proof) ? json_decode($order->order_proof, true) : [])
                @if ( in_array($order->order_status, [ 'handover', 'delivered', 'picked_up']) || ($data != null && count($data) > 0) )

                <!-- order proof -->
                <div class="card mb-2 mt-2">
                    <div class="card-header border-0 text-center pb-0">
                        <h4 class="m-0">{{ translate('messages.delivery_proof') }} </h4>
                        @if ( in_array($order->order_status, [ 'handover', 'delivered', 'picked_up']) )
                            <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target=".order-proof-modal">  {{ translate('messages.add') }}  </button>
                        @endif
                    </div>
                    <div class="card-body pt-2">
                        @if ($data)
                        <label class="input-label"
                            for="order_proof">{{ translate('messages.image') }} : </label>
                        <div class="row g-3">
                                @foreach ($data as $key => $img)
                                @php($img = is_array($img)?$img:['img'=>$img,'storage'=>'public'])
                                    <div class="col-3">
                                        <img class="img__aspect-1 rounded border w-100 onerror-image" data-toggle="modal"
                                            data-target="#imagemodal{{ $key }}"
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                            src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}">
                                    </div>
                                    <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1"
                                        role="dialog" aria-labelledby="order_proof_{{ $key }}"
                                        aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h4 class="modal-title"
                                                        id="order_proof_{{ $key }}">
                                                        {{ translate('order_proof_image') }}</h4>
                                                        <button type="button" class="close"
                                                        data-dismiss="modal"><span
                                                            aria-hidden="true">&times;</span><span
                                                            class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                                </div>
                                                <div class="modal-body">
                                                    <img src="{{\App\CentralLogics\Helpers::get_full_url('order',$img['img'],$img['storage']) }}"
                                                        class="initial--22 w-100">
                                                </div>
                                                @php($storage = $img['storage'] ?? 'public')
                                                @php($file = $storage == 's3'?base64_encode('order/' . $img['img']):base64_encode('public/order/' . $img['img']))
                                                <div class="modal-footer">
                                                    <a class="btn btn-primary"
                                                        href="{{ route('admin.file-manager.download', [$file,$storage]) }}"><i
                                                            class="tio-download"></i>
                                                        {{ translate('messages.download') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                @endif

                    @if ($order->store)
                    <!-- Restaurant Card -->
                    <div class="card mt-2">
                        <!-- Body -->
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <span class="card-header-icon">
                                    <i class="tio-user"></i>
                                </span>
                                <span>{{ translate('messages.store_information') }}</span>
                            </h5>
                            <a class="media align-items-center deco-none resturant--information-single"
                                href="{{ route('admin.store.view', [$order->store['id'],'module_id' => $order->module_id]) }}">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img w-75px onerror-image"
                                        data-onerror-image="{{ asset('public/assets/admin/img/100x100/1.png') }}"
                                        src="{{$order?->store?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png')  }}"
                                        alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="fz--14px text--title font-semibold text-hover-primary d-block">
                                        {{ $order->store['name'] }}
                                    </span>
                                    <span>{{ $order->store->orders_count }} {{ translate('messages.orders') }}</span>
                                    <span class="text--title font-semibold d-flex align-items-center">
                                        <i class="tio-call-talking-quiet mr-2"></i>{{ $order->store['phone'] }}
                                    </span>
                                    <span class="text--title d-flex align-items-center">
                                        <i class="tio-email mr-2"></i>{{ $order->store['email'] }}
                                    </span>
                                </div>
                            </a>
                            <hr>
                            <span class="d-block">
                                <a target="_blank" class="d-flex align-items-center __gap-5px" href="http://maps.google.com/maps?z=12&t=m&q=loc:{{ $order->store['latitude'] }}+{{ $order->store['longitude'] }}">
                                    <i class="tio-poi"></i> <span>{{ $order->store['address'] }}</span><br>
                                </a>
                            </span>
                        </div>
                        <!-- End Body -->
                    </div>
                    <!-- End Card -->
                @endif
            </div>
        </div>
        <!-- End Row -->
    </div>

    <!-- Modal -->
    <div class="modal fade" id="refund_cancelation_note" tabindex="-1" role="dialog"
        aria-labelledby="refund_cancelation_note_l" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refund_cancelation_note_l">{{ translate('messages.add_Order Rejection_Note') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.refund.order_refund_rejection') }}" method="post">
                        @method('PUT')
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="text" class="form-control" name="admin_note" value="{{ old('admin_note') }}"
                            placeholder="Fake Order">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{  translate('close') }}</button>
                    <button type="submit" class="btn btn-danger">{{ translate('messages.Confirm_Order Rejection') }} </button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade bd-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.reference_code_add') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                        aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-payment-ref-code', [$order['id']]) }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <!-- Input Group -->
                        <div class="form-group">
                            <input type="text" name="transaction_reference" class="form-control"
                                placeholder="{{ translate('messages.Ex:') }} Code123" required>
                        </div>
                        <!-- End Input Group -->
                        <div class="text-right">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->
    <!-- Modal -->
    <div class="modal fade order-proof-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h4" id="mySmallModalLabel">{{ translate('messages.add_delivery_proof') }}</h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                        aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{ route('admin.order.add-order-proof', [$order['id']]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="flex-grow-1 mx-auto">
                            <div class="d-flex flex-wrap __gap-12px __new-coba" id="coba">
                                @php($proof = isset($order->order_proof) ? json_decode($order->order_proof, true) : 0)
                                @if ($proof)

                                @foreach ($proof as $key => $photo)
                                        @php($photo = is_array($photo)?$photo:['img'=>$photo,'storage'=>'public'])
                                            <div class="spartan_item_wrapper min-w-176px max-w-176px">
                                                <img class="img--square"
                                                     src="{{\App\CentralLogics\Helpers::get_full_url('order',$photo['img'],$photo['storage']) }}"
                                                    alt="order image">
                                                <div class="pen spartan_remove_row"><i class="tio-edit"></i></div>
                                                <a href="{{ route('admin.order.remove-proof-image', ['id' => $order['id'], 'name' => $photo['img']]) }}"
                                                    class="spartan_remove_row"><i class="tio-add-to-trash"></i></a>
                                            </div>
                                        @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="text-right mt-2">
                            <button class="btn btn--primary">{{ translate('messages.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!-- Modal -->
    <div id="shipping-address-modal" class="modal fade" tabindex="-1" role="dialog"
        aria-labelledby="exampleModalTopCoverTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-top-cover bg-dark text-center">
                    <figure class="position-absolute right-0 bottom-0 left-0 mb--1">
                        <svg preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                            viewBox="0 0 1920 100.1">
                            <path fill="#fff" d="M0,0c0,0,934.4,93.4,1920,0v100.1H0L0,0z" />
                        </svg>
                    </figure>

                    <div class="modal-close">
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-light" data-dismiss="modal"
                            aria-label="Close">
                            <svg width="16" height="16" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor"
                                    d="M11.5,9.5l5-5c0.2-0.2,0.2-0.6-0.1-0.9l-1-1c-0.3-0.3-0.7-0.3-0.9-0.1l-5,5l-5-5C4.3,2.3,3.9,2.4,3.6,2.6l-1,1 C2.4,3.9,2.3,4.3,2.5,4.5l5,5l-5,5c-0.2,0.2-0.2,0.6,0.1,0.9l1,1c0.3,0.3,0.7,0.3,0.9,0.1l5-5l5,5c0.2,0.2,0.6,0.2,0.9-0.1l1-1 c0.3-0.3,0.3-0.7,0.1-0.9L11.5,9.5z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- End Header -->

                <div class="modal-top-cover-icon">
                    <span class="icon icon-lg icon-light icon-circle icon-centered shadow-soft">
                        <i class="tio-location-search"></i>
                    </span>
                </div>

                @if (isset($address))
                    <form action="{{ route('admin.order.update-shipping', [$order['id']]) }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.type') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address_type"
                                        value="{{ $address['address_type'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.contact') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_number"
                                        value="{{ $address['contact_person_number'] }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.name') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="contact_person_name"
                                        value="{{ $address['contact_person_name'] }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('House') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="house"
                                        value="{{ isset($address['house']) ? $address['house'] : '' }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Floor') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="floor"
                                        value="{{ isset($address['floor']) ? $address['floor'] : '' }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('Road') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="road"
                                        value="{{ isset($address['road']) ? $address['road'] : '' }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.address') }}
                                </label>
                                <div class="col-md-10 js-form-message">
                                    <input type="text" class="form-control" name="address"
                                        value="{{ $address['address'] }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.latitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="latitude" id="latitude"
                                        value="{{ $address['latitude'] }}">
                                </div>
                                <label for="requiredLabel" class="col-md-2 col-form-label input-label text-md-right">
                                    {{ translate('messages.longitude') }}
                                </label>
                                <div class="col-md-4 js-form-message">
                                    <input type="text" class="form-control" name="longitude" id="longitude"
                                        value="{{ $address['longitude'] }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <input id="pac-input" class="controls rounded initial-8"
                                    title="{{ translate('messages.search_your_location_here') }}" type="text"
                                    placeholder="{{ translate('messages.search_here') }}" />
                                <div class="mb-2 h-200px" id="map"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn--reset"
                                data-dismiss="modal">{{ translate('messages.close') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('messages.save_changes') }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Dm assign Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">{{ translate('messages.assign_deliveryman') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5 my-2">
                            <ul class="list-group overflow-auto initial--23">
                                @foreach ($deliveryMen as $dm)
                                    <li class="list-group-item">
                                        <span class="dm_list" role='button' data-id="{{ $dm['id'] }}">
                                            <img class="avatar avatar-sm avatar-circle mr-1 onerror-image"
                                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                                src="{{$dm['image_full_url'] }}"
                                                alt="{{ $dm['name'] }}">
                                            {{ $dm['name'] }}
                                        </span>

                                        <a class="btn btn-primary btn-xs float-right add-delivery-man" data-id="{{ $dm['id'] }}">{{ translate('messages.assign') }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col-md-7 modal_body_map">
                            <div class="location-map" id="dmassign-map">
                                <div class="initial--24" id="map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->
    <input type="hidden" id="order_id" value="{{ $order->id }}">

    <!--Show locations on map Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="locationModalLabel">{{ translate('messages.location_data') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 modal_body_map">
                            <div class="location-map" id="location-map">
                                <div class="initial--25" id="location_map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="modal fade" id="quick-view" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" id="quick-view-modal">

            </div>
        </div>
    </div>



    @if ($order?->offline_payments)
    <div class="modal fade" id="verifyViewModal" tabindex="-1" aria-labelledby="verifyViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-end  border-0">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true" class="tio-clear"></span>
                    </button>
                </div>
            <div class="modal-body">
            <div class="d-flex align-items-center flex-column gap-3 text-center">
                <h2>{{translate('Payment_Verification')}}
                    @if ($order?->offline_payments->status == 'verified')
                        <span class="badge badge-soft-success mt-3 mb-3">{{ translate('messages.verified') }}</span>
                    @endif
                </h2>
                <p class="text-danger mb-2 mt-2">{{ translate('Please_Check_&_Verify_the_payment_information_weather_it_is_correct_or_not_before_confirm_the_order.') }}</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">{{ translate('messages.customer_information') }}</h4>
                        <div class="d-flex flex-column gap-2">
                            @if($order->is_guest)
                            @php($customer_details = json_decode($order['delivery_address'],true))

                            <div class="d-flex align-items-center gap-2">
                                <span>{{translate('Name')}}</span>:
                                <span class="text-dark"> {{$customer_details['contact_person_name']}}</span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span>{{translate('Phone')}}</span>:
                                <span class="text-dark">  {{$customer_details['contact_person_number']}}</span>
                            </div>

                            @elseif($order->customer)
                            <div class="d-flex align-items-center gap-2">
                                <span>{{translate('Name')}}</span>:
                                <span class="text-dark"> <a class="text-body text-capitalize" href="{{route('admin.customer.view',[$order['user_id']])}}"> {{$order->customer['f_name'].' '.$order->customer['l_name']}}  </a>  </span>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span>{{translate('Phone')}}</span>:
                                <span class="text-dark">{{$order->customer['phone']}}  </span>
                            </div>

                            @else
                                <label class="badge badge-danger">{{translate('messages.invalid_customer_data')}}</label>
                            @endif

                        </div>

                            <div class="mt-5">
                                <h4 class="mb-3">{{ translate('messages.Payment_Information') }}</h4>
                                <div class="row g-3">
                                    @foreach (json_decode($order->offline_payments->payment_info) as $key=>$item)
                                        @if ($key != 'method_id')
                                        <div class="col-sm-6  col-lg-5">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="w-sm-25"> {{translate($key)}}</span>:
                                                <span class="text-dark text-break">{{ $item }}</span>
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>

                                <div class="d-flex flex-column gap-2 mt-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span>{{translate('Customer_Note')}}</span>:
                                        <span class="text-dark text-break">{{$order->offline_payments?->customer_note ?? translate('messages.N/A')}} </span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    @if ($order?->offline_payments->status != 'verified')
                    <div class="btn--container justify-content-end mt-3">
                        @if ($order?->offline_payments->status != 'denied')
                        <button type="button" class="btn btn--danger btn-outline-danger offline_payment_cancelation_note" data-toggle="modal" data-target="#offline_payment_cancelation_note" data-id="{{ $order['id'] }}" class="btn btn--reset">{{translate('Payment_Didn’t_Recerive')}}</button>
                        @elseif ($order?->offline_payments->status == 'denied')
                            <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'switched_to_cod', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}" class="btn btn-info mb-2 route-alert">{{translate('Switched_to_COD')}}</button>
                        @endif

                        <button type="button" data-url="{{ route('admin.order.offline_payment', [ 'id' => $order['id'], 'verify' => 'yes', ]) }}" data-message="{{ translate('messages.Make_the_payment_verified_for_this_order') }}" class="btn btn--primary mb-2 route-alert">{{translate('Yes,_Payment_Received')}}</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

                <!-- Modal -->
                <div class="modal fade" id="offline_payment_cancelation_note" tabindex="-1" role="dialog"
                aria-labelledby="offline_payment_cancelation_note_l" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="offline_payment_cancelation_note_l">{{ translate('messages.Add_Offline_Payment_Rejection_Note') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('admin.order.offline_payment') }}" method="get">
                                <input type="hidden" name="id" value="{{ $order->id }}">
                                <input type="text" required class="form-control" name="note" value="{{ old('note') }}"
                                    placeholder="{{ translate('transaction_id_mismatched') }}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{  translate('close') }}</button>
                            <button type="submit" class="btn btn--danger btn-outline-danger">{{ translate('messages.Confirm_Rejection') }} </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <!-- End Modal -->
@endsection

@push('script_2')
    <script>
               $(document).on('keydown', '.item-qty-input', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();

        let id = $(this).data('id');
        let orderId = $('#order_id').val();
        let quantity = $(this).val();

        $.get({
            url: '{{ route('admin.order.update-quantity') }}',
            dataType: 'json',
            data: {
                id: id,
                order_id: orderId,
                qty: quantity
            },
            beforeSend: function() {
                $('#loading').show();
            },
            success: function(data) {
                toastr.success("{{ translate('messages.success') }}", {
                    closeButton: true,
                    progressBar: true
                });
                window.location.reload();
            },
            complete: function() {
                $('#loading').hide();
            },
            error: function(xhr) {
                toastr.error('حدث خطأ أثناء الحفظ');
            }
        });
    }
    });
        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            var keyword = $('#datatableSearch').val();
            var nurl = new URL('{!! url()->full() !!}');
            nurl.searchParams.set('keyword', keyword);
            location.href = nurl;
        });

        $('.set-category-filter').on('change', function() {
            let id = $(this).val();
            var nurl = new URL('{!! url()->full() !!}');
            nurl.searchParams.set('category_id', id);
            location.href = nurl;
        })

        $('.addon_quantity_input_toggle').on('change', function(event) {
            addon_quantity_input_toggle(event);
        })

        function addon_quantity_input_toggle(e) {
            var cb = $(e.target);
            if (cb.is(":checked")) {
                cb.siblings('.addon-quantity-input').css({
                    'visibility': 'visible'
                });
            } else {
                cb.siblings('.addon-quantity-input').css({
                    'visibility': 'hidden'
                });
            }
        }

        $('.quick-view-cart-item').on('click',function (){
            let key = $(this).data('key');
            $.get({
                url: '{{ route('admin.order.quick-view-cart-item') }}',
                dataType: 'json',
                data: {
                    key: key,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        })

        $('.quick-view').on('click',function (){
            let product_id = $(this).data('product-id');
            quickView(product_id);
        })

        function quickView(product_id) {
            $.get({
                url: '{{ route('admin.order.quick-view') }}',
                dataType: 'json',
                data: {
                    product_id: product_id,
                    order_id: '{{ $order->id }}',
                },
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    console.log("success...")
                    $('#quick-view').modal('show');
                    $('#quick-view-modal').empty().html(data.view);
                },
                complete: function() {
                    $('#loading').hide();
                },
            });
        }

        function cartQuantityInitialize() {
            $('.btn-number').click(function(e) {
                e.preventDefault();

                var fieldName = $(this).attr('data-field');
                var type = $(this).attr('data-type');
                var input = $("input[name='" + fieldName + "']");
                var currentVal = parseInt(input.val());

                if (!isNaN(currentVal)) {
                    if (type == 'minus') {

                        if (currentVal > input.attr('min')) {
                            input.val(currentVal - 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('min')) {
                            $(this).attr('disabled', true);
                        }

                    } else if (type == 'plus') {

                        if (currentVal < input.attr('max')) {
                            input.val(currentVal + 1).change();
                        }
                        if (parseInt(input.val()) == input.attr('max')) {
                            $(this).attr('disabled', true);
                        }

                    }
                } else {
                    input.val(0);
                }
            });

            $('.input-number').focusin(function() {
                $(this).data('oldValue', $(this).val());
            });

            $('.input-number').change(function() {

                minValue = parseInt($(this).attr('min'));
                maxValue = parseInt($(this).attr('max'));
                valueCurrent = parseInt($(this).val());

                var name = $(this).attr('name');
                if (valueCurrent >= minValue) {
                    $(".btn-number[data-type='minus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, the minimum value was reached'
                    });
                    $(this).val($(this).data('oldValue'));
                }
                if (valueCurrent <= maxValue) {
                    $(".btn-number[data-type='plus'][data-field='" + name + "']").removeAttr('disabled')
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cart',
                        text: 'Sorry, stock limit exceeded.'
                    });
                    $(this).val($(this).data('oldValue'));
                }
            });
            $(".input-number").keydown(function(e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
                    // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                    // Allow: home, end, left, right
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        }

        function getVariantPrice() {
            if ($('#add-to-cart-form input[name=quantity]').val() > 0) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
                $.ajax({
                    type: "POST",
                    url: '{{ route('admin.item.variant-price') }}',
                    data: $('#add-to-cart-form').serializeArray(),
                    success: function(data) {
                        $('#add-to-cart-form #chosen_price_div').removeClass('d-none');
                        $('#add-to-cart-form #chosen_price_div #chosen_price').html(data.price);
                    }
                });
            }
        }


        $(document).on('click', '.update_order_item', function () {


            update_order_item();
        })

        function update_order_item(form_id = 'add-to-cart-form') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('admin.order.add-to-cart') }}',
                data: $('#' + form_id).serializeArray(),
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    if (data.data == 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Cart',
                            text: "{{ translate('messages.product_already_added_in_cart') }}"
                        });
                        return false;
                    } else if (data.data == 0) {
                        toastr.success('{{ translate('messages.product_has_been_added_in_cart') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        location.reload();
                        return false;
                    } else if (data.data == 'variation_error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Cart',
                            text: data.message
                        });
                        return false;
                    }
                    $('.call-when-done').click();

                    toastr.success('{{ translate('messages.order_updated_successfully') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    location.reload();
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }


        $(document).on('click', '.removeFromCart', function () {
            let key = $(this).data('key');
            removeFromCart(key);
        })

        function removeFromCart(key) {
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_remove_this_order_item') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.post('{{ route('admin.order.remove-from-cart') }}', {
                        _token: '{{ csrf_token() }}',
                        key: key,
                        order_id: '{{ $order->id }}'
                    }, function(data) {
                        if (data.errors) {
                            for (var i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        } else {
                            toastr.success(
                                '{{ translate('messages.item_has_been_removed_from_cart') }}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            location.reload();
                        }

                    });
                }
            })

        }

        $('.edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_edit_this_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('admin.order.edit', $order->id) }}';
                }
            })
        })

        $('.cancel-edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_cancel_editing') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('admin.order.edit', $order->id) }}?cancle=true';
                }
            })
        })

        $('.submit-edit-order').on('click',function (){
            Swal.fire({
                title: '{{ translate('messages.are_you_sure') }}',
                text: '{{ translate('messages.you_want_to_submit_all_changes_for_this_order') }}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = '{{ route('admin.order.update', $order->id) }}';
                }
            })
        })
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8">
    </script>
    <script>
        
            // INITIALIZATION OF SELECT2
        // =======================================================$
 



        $('.js-select2-custom').each(function () {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });

            $('.add-delivery-man').on('click',function (){
                id = $(this).data('id');
            $.ajax({
                type: "GET",
                url: '{{ url('/') }}/admin/order/add-delivery-man/{{ $order['id'] }}/' + id,
                success: function(data) {
                    location.reload();
                    console.log(data)
                    toastr.success('Successfully added', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                error: function(response) {
                    console.log(response);
                    toastr.error(response.responseJSON.message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        })

        $('.order_status_change_alert').on('click', function (){
            let route = $(this).data('url');
            let message = $(this).data('message');
            let processing = $(this).data('processing');
            order_status_change_alert(route, message, processing);
        })

        function order_status_change_alert(route, message, processing = false) {
            if (processing) {
                Swal.fire({
                    //text: message,
                    title: '{{ translate('messages.Are you sure ?') }}',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.Cancel') }}',
                    confirmButtonText: '{{ translate('messages.submit') }}',
                    inputPlaceholder: "{{ translate('Enter processing time') }}",
                    input: 'text',
                    html: message + '<br/>'+'<label>{{ translate('Enter Processing time in minutes') }}</label>',
                    inputValue: processing,
                    preConfirm: (processing_time) => {
                        location.href = route + '&processing_time=' + processing_time;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                })
            } else {
                Swal.fire({
                    title: '{{ translate('messages.Are you sure ?') }}',
                    text: message,
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: 'default',
                    confirmButtonColor: '#FC6A57',
                    cancelButtonText: '{{ translate('messages.No') }}',
                    confirmButtonText: '{{ translate('messages.Yes') }}',
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        location.href = route;
                    }
                })
            }
        }


        function last_location_view() {
            toastr.warning('Only available when order is out for delivery!', {
                CloseButton: true,
                ProgressBar: true
            });
        }
        $(document).ready(function () {
                // Event handler for 'canceled-status' click
                $('.canceled-status').on('click', function () {
                    // Assuming $reasons is properly populated and contains reasons

                    // Create a select dropdown with options using map()
                    var selectOptions = '';
                    @foreach ($reasons as $r)
                        selectOptions += `<option value="{{ $r->reason }}">{{ $r->reason }}</option>`;
                    @endforeach

                    // Generate the Swal modal with the select dropdown
                    Swal.fire({
                        title: '{{ translate('messages.are_you_sure') }}',
                        text: '{{ translate('messages.Change status to canceled ?') }}',
                        type: 'warning',
                        html: `<select class="form-control js-select2-custom mx-1" name="reason" id="reason">${selectOptions}</select>`,
                        showCancelButton: true,
                        cancelButtonColor: 'default',
                        confirmButtonColor: '#FC6A57',
                        cancelButtonText: '{{ translate('messages.no') }}',
                        confirmButtonText: '{{ translate('messages.yes') }}',
                        reverseButtons: true,
                        onOpen: function () {
                            // Initialize select2 after the modal is opened
                            $('.js-select2-custom').select2({
                                minimumResultsForSearch: 5,
                                width: '100%',
                                placeholder: "Select Reason",
                                language: "en",
                            });
                        }
                    }).then((result) => {
                        if (result.value) {
                            // On confirmation, get the selected reason and redirect
                            var reason = $('#reason').val();
                            var orderID = '{{ $order['id'] }}';
                            var statusRoute = '{{ route('admin.order.status') }}';

                            // Redirect with order ID, status, and reason
                            var redirectURL = `${statusRoute}?id=${orderID}&order_status=canceled&reason=${reason}`;

                            // Redirect the user to the generated URL
                            window.location.href = redirectURL;
                        }
                    });
                });
            });
    </script>
    <script>
        var deliveryMan = <?php echo json_encode($deliveryMen); ?>;
        var map = null;
        @if ($order->order_type == 'parcel')
            var myLatlng = new google.maps.LatLng({{ $address['latitude'] }}, {{ $address['longitude'] }});
        @else
            @php($default_location = App\CentralLogics\Helpers::get_business_settings('default_location'))
            var myLatlng = new google.maps.LatLng(
                {{ isset($order->store) ? $order->store->latitude : (isset($default_location) ? $default_location['lat'] : 0) }},
                {{ isset($order->store) ? $order->store->longitude : (isset($default_location['lng']) ? $default_location['lng'] : 0) }}
            );
        @endif
        var dmbounds = new google.maps.LatLngBounds(null);
        var locationbounds = new google.maps.LatLngBounds(null);
        var dmMarkers = [];
        dmbounds.extend(myLatlng);
        locationbounds.extend(myLatlng);
        var myOptions = {
            center: myLatlng,
            zoom: 13,
            mapTypeId: google.maps.MapTypeId.ROADMAP,

            panControl: true,
            mapTypeControl: false,
            panControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            zoomControl: true,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.LARGE,
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            scaleControl: false,
            streetViewControl: false,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            }
        };
        function printCard() {
    const cardBody = document.querySelector('.card-body.px-0');
    const total = document.querySelector('.row.justify-content-md-end.mb-3.mt-4.mx-0');

    
    if (cardBody) {
        const printWindow = window.open('', '_blank');
        printWindow.document.open();

        // كتابة الروابط داخل نافذة الطباعة
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>طباعة</title>
                <link href="{{ asset('public/assets/admin/css/fonts.css') }}" rel="stylesheet">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/vendor.min.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/owl.min.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.minc619.css?v=1.0') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap-tour-standalone.min.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/emogi-area.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
                <link rel="stylesheet" href="{{ asset('public/assets/admin/intltelinput/css/intlTelInput.css') }}">
                
            </head>
            <body>
                  <div class="text-center pt-4 mb-3">
                        <img class="invoice-logo" src="{{ asset('/public/assets/admin/img/invoice-logo.png') }}"
                            alt="">
                        <div class="top-info">
                            <h2 class="store-name">ميديكل ستور</h2>
                            <div>
                                 {{ \App\Models\BusinessSetting::where('key', 'address')->first()->value }}
                            </div>
                            <div class="mt-1 d-flex justify-content-center">
                                <span>{{ translate('messages.phone') }}</span> <span>:</span> <span>{{ \App\Models\BusinessSetting::where('key', 'phone')->first()->value }}</span>
                            </div>
                        </div>
                    </div>
                ${cardBody.outerHTML} <!-- إدراج المحتوى -->
            </body>
            </html>
        `);

        printWindow.document.close();

        // الانتظار حتى يتم تحميل الموارد
        printWindow.onload = () => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        };
    } else {
        alert('العنصر المطلوب للطباعة غير موجود!');
    }
}
        function initializeGMap() {

            map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

            var infowindow = new google.maps.InfoWindow();
            @if ($order->store)
                var Restaurantmarker = new google.maps.Marker({
                    @if ($parcel_order)
                        position: new google.maps.LatLng({{ $address['latitude'] }},
                            {{ $address['longitude'] }}),
                        title: "{{ Str::limit($order->customer->f_name . ' ' . $order->customer->l_name, 15, '...') }}",
                        // icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}"
                    @else
                        position: new google.maps.LatLng({{ $order->store->latitude }},
                            {{ $order->store->longitude }}),
                        title: "{{ Str::limit($order?->store?->name, 15, '...') }}",
                        icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}",
                    @endif
                    map: map,

                });

                google.maps.event.addListener(Restaurantmarker, 'click', (function(Restaurantmarker) {
                    return function() {
                        @if ($parcel_order)
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->customer?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div style='float:right; padding: 10px;'><b>{{ $order->customer->f_name }}{{ $order->customer->l_name }}</b><br />{{ $address['address'] }}</div>"
                            );
                        @else
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->store?->logo_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div class='text-break' style='float:right; padding: 10px;'><b>{{ Str::limit($order?->store?->name, 15, '...') }}</b><br /> {{ $order->store->address }}</div>"
                            );
                        @endif
                        infowindow.open(map, Restaurantmarker);
                    }
                })(Restaurantmarker));
            @endif

            map.fitBounds(dmbounds);
            for (var i = 0; i < deliveryMan.length; i++) {
                if (deliveryMan[i].lat) {
                    // var contentString = "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ asset('storage/app/public/delivery-man') }}/"+deliveryMan[i].image+"'></div><div style='float:right; padding: 10px;'><b>"+deliveryMan[i].name+"</b><br/> "+deliveryMan[i].location+"</div>";
                    var point = new google.maps.LatLng(deliveryMan[i].lat, deliveryMan[i].lng);
                    dmbounds.extend(point);
                    map.fitBounds(dmbounds);
                    var marker = new google.maps.Marker({
                        position: point,
                        map: map,
                        title: deliveryMan[i].location,
                        icon: "{{ asset('public/assets/admin/img/delivery_boy_map.png') }}"
                    });
                    dmMarkers[deliveryMan[i].id] = marker;
                    google.maps.event.addListener(marker, 'click', (function(marker, i) {
                        return function() {
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='"+ deliveryMan[i].image_link +"'></div><div style='float:right; padding: 10px;'><b>" + deliveryMan[i].name + "</b><br/> " + deliveryMan[i].location + "</div>");
                            infowindow.open(map, marker);
                        }
                    })(marker, i));
                }

            };
        }

        function initMap() {
            let map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: {
                    lat: {{ isset($order->store) ? $order->store->latitude : '23.757989' }},
                    lng: {{ isset($order->store) ? $order->store->longitude : '90.360587' }}
                }
            });

            let zonePolygon = null;

            //get current location block
            let infoWindow = new google.maps.InfoWindow();
            // Try HTML5 geolocation.
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        myLatlng = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        infoWindow.setPosition(myLatlng);
                        infoWindow.setContent("Location found.");
                        infoWindow.open(map);
                        map.setCenter(myLatlng);
                    },
                    () => {
                        handleLocationError(true, infoWindow, map.getCenter());
                    }
                );
            } else {
                // Browser doesn't support Geolocation
                handleLocationError(false, infoWindow, map.getCenter());
            }
            //-----end block------
            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
            let markers = [];
            const bounds = new google.maps.LatLngBounds();
            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }
                // Clear out the old markers.
                markers.forEach((marker) => {
                    marker.setMap(null);
                });
                markers = [];
                // For each place, get the icon, name and location.
                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        console.log("Returned place contains no geometry");
                        return;
                    }
                    console.log(place.geometry.location);
                    if (!google.maps.geometry.poly.containsLocation(
                            place.geometry.location,
                            zonePolygon
                        )) {
                        toastr.error('{{ translate('messages.out_of_coverage') }}', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        return false;
                    }

                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();

                    const icon = {
                        url: place.icon,
                        size: new google.maps.Size(71, 71),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(17, 34),
                        scaledSize: new google.maps.Size(25, 25),
                    };
                    // Create a marker for each place.
                    markers.push(
                        new google.maps.Marker({
                            map,
                            icon,
                            title: place.name,
                            position: place.geometry.location,
                        })
                    );

                    if (place.geometry.viewport) {
                        // Only geocodes have viewport.
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(place.geometry.location);
                    }
                });
                map.fitBounds(bounds);
            });
            @if ($order->store)
                $.get({
                    url: '{{ url('/') }}/admin/zone/get-coordinates/{{ $order->store->zone_id }}',
                    dataType: 'json',
                    success: function(data) {
                        zonePolygon = new google.maps.Polygon({
                            paths: data.coordinates,
                            strokeColor: "#FF0000",
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                            fillColor: 'white',
                            fillOpacity: 0,
                        });
                        zonePolygon.setMap(map);
                        zonePolygon.getPaths().forEach(function(path) {
                            path.forEach(function(latlng) {
                                bounds.extend(latlng);
                                map.fitBounds(bounds);
                            });
                        });
                        map.setCenter(data.center);
                        google.maps.event.addListener(zonePolygon, 'click', function(mapsMouseEvent) {
                            infoWindow.close();
                            // Create a new InfoWindow.
                            infoWindow = new google.maps.InfoWindow({
                                position: mapsMouseEvent.latLng,
                                content: JSON.stringify(mapsMouseEvent.latLng.toJSON(), null,
                                    2),
                            });
                            var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                            var coordinates = JSON.parse(coordinates);

                            document.getElementById('latitude').value = coordinates['lat'];
                            document.getElementById('longitude').value = coordinates['lng'];
                            infoWindow.open(map);
                        });
                    },
                });
            @endif

        }

        $(document).ready(function() {

            // Re-init map before show modal
            $('#myModal').on('shown.bs.modal', function(event) {
                initMap();
                var button = $(event.relatedTarget);
                $("#dmassign-map").css("width", "100%");
                $("#map_canvas").css("width", "100%");
            });

            // Trigger map resize event after modal shown
            $('#myModal').on('shown.bs.modal', function() {
                initializeGMap();
                google.maps.event.trigger(map, "resize");
                map.setCenter(myLatlng);
            });

            // Address change modal modal shown
            $('#shipping-address-modal').on('shown.bs.modal', function() {
                initMap();
                // google.maps.event.trigger(map, "resize");
                // map.setCenter(myLatlng);
            });


            function initializegLocationMap() {
                map = new google.maps.Map(document.getElementById("location_map_canvas"), myOptions);

                var infowindow = new google.maps.InfoWindow();

                @if ($order->customer && isset($address))
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $address['latitude'] }},
                            {{ $address['longitude'] }}),
                        map: map,
                        title: "{{ $order->customer->f_name }} {{ $order->customer->l_name }}",
                        icon: "{{ asset('public/assets/admin/img/customer_location.png') }}"
                    });

                    google.maps.event.addListener(marker, 'click', (function(marker) {
                        return function() {
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->customer?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div><div style='float:right; padding: 10px;'><b>{{ $order->customer->f_name }} {{ $order->customer->l_name }}</b><br />{{ $address['address'] }}</div>"
                            );
                            infowindow.open(map, marker);
                        }
                    })(marker));
                    locationbounds.extend(marker.getPosition());
                @endif
                @if ($order->delivery_man && $order->dm_last_location)
                    var dmmarker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $order->dm_last_location['latitude'] }},
                            {{ $order->dm_last_location['longitude'] }}),
                        map: map,
                        title: "{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}",
                        icon: "{{ asset('public/assets/admin/img/delivery_boy_map.png') }}"
                    });

                    google.maps.event.addListener(dmmarker, 'click', (function(dmmarker) {
                        return function() {
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->delivery_man?->image_full_url ?? asset('public/assets/admin/img/160x160/img1.jpg') }}'></div> <div style='float:right; padding: 10px;'><b>{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</b><br /> {{ $order->dm_last_location['location'] }}</div>"
                            );
                            infowindow.open(map, dmmarker);
                        }
                    })(dmmarker));
                    locationbounds.extend(dmmarker.getPosition());
                @endif

                @if ($order->store)
                    var Retaurantmarker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $order->store->latitude }},
                            {{ $order->store->longitude }}),
                        map: map,
                        title: "{{ Str::limit($order?->store?->name, 15, '...') }}",
                        icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}"
                    });

                    google.maps.event.addListener(Retaurantmarker, 'click', (function(Retaurantmarker) {
                        return function() {
                            infowindow.setContent(
                                "<div style='float:left'><img style='max-height:40px;wide:auto;' src='{{ $order?->store?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}'></div> <div style='float:right; padding: 10px;'><b>{{ Str::limit($order?->store?->name, 15, '...') }}</b><br /> {{ $order->store->address }}</div>"
                            );
                            infowindow.open(map, Retaurantmarker);
                        }
                    })(Retaurantmarker));
                    locationbounds.extend(Retaurantmarker.getPosition());
                @endif
                @if ($parcel_order && isset($receiver_details))
                    var Receivermarker = new google.maps.Marker({
                        position: new google.maps.LatLng({{ $receiver_details['latitude'] }},
                            {{ $receiver_details['longitude'] }}),
                        map: map,
                        title: "{{ Str::limit($receiver_details['contact_person_name'], 15, '...') }}",
                        // icon: "{{ asset('public/assets/admin/img/restaurant_map.png') }}"
                    });

                    google.maps.event.addListener(Receivermarker, 'click', (function(Receivermarker) {
                        return function() {
                            infowindow.open(map, Receivermarker);
                        }
                    })(Receivermarker));
                    locationbounds.extend(Receivermarker.getPosition());
                @endif

                google.maps.event.addListenerOnce(map, 'idle', function() {
                    map.fitBounds(locationbounds);
                });
            }

            // Re-init map before show modal
            $('#locationModal').on('shown.bs.modal', function(event) {
                initializegLocationMap();
            });


            $('.dm_list').on('click', function() {
                var id = $(this).data('id');
                map.panTo(dmMarkers[id].getPosition());
                map.setZoom(13);
                dmMarkers[id].setAnimation(google.maps.Animation.BOUNCE);
                window.setTimeout(() => {
                    dmMarkers[id].setAnimation(null);
                }, 3);
            });
        })
    </script>

    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'order_proof[]',
                maxCount: 6-{{ ($order->order_proof && is_array($order->order_proof))?count(json_decode($order->order_proof)):0 }},
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: '',
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '176px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error(
                        "{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                            CloseButton: true,
                            ProgressBar: true
                        });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });
    </script>
@endpush
