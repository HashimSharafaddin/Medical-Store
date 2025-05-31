@extends('layouts.admin.app')

@section('title',translate('messages.deliverymen_earning_provide'))

@push('css_or_js')

@endpush
@php
$withdrawal_methods= \App\Models\WithdrawalMethod::get();
@endphp
@section('content')
<div class="content container-fluid">
    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/report.png')}}" class="w--22" alt="">
            </span>
            <span>
                {{translate('messages.provide_deliverymen_earning')}}
            </span>
        </h1>
    </div>
    <!-- Page Heading -->
    <div class="card">
        <div class="card-body">
            <form action="{{route('admin.transactions.provide-deliveryman-earnings.store')}}" method='post' id="add_transaction">
                @csrf
                <div class="row g-3">
                    <div class="col-sm-6">

                    <div class="form-group mb-0">
                        <label class="form-label" for="pay_type">{{_('نوع العمليه')}}<span class="input-label-secondary"></span></label>
                        <select id="pay_type"class="form-control " name="pay_type" data-placeholder="{{_('دفع')}}">
                            <option value="1">{{ _('صرف') }}</option>
                            <option value="0">{{ _('قبص') }}</option>                            </select>
                    </div>
                </div>
                   

                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="deliveryman">{{translate('messages.deliveryman')}}<span class="input-label-secondary"></span></label>
                            <select id="deliveryman" name="deliveryman_id" data-placeholder="{{translate('messages.select_deliveryman')}}" data-url="{{url('/')}}/admin/users/delivery-man/get-account-data/" data-type="deliveryman" class="form-control account-data" title="Select deliveryman">

                            </select>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="amount">{{translate('messages.amount')}}<span class="input-label" id="account_info"></span></label>
                            <input class="form-control" type="number" step="0.01" name="amount" id="amount" max="999999999999.99" placeholder="{{translate('ex_100')}}">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="method">{{translate('messages.payment_method')}}<span class="input-label-secondary"></span></label>

                    <select class="form-control" id="withdraw_method" name="withdraw_method" required>

                        <option value="" selected disabled>{{translate('Select_Withdraw_Method')}}</option>
                        @foreach($withdrawal_methods as $item)
                            <option value="{{$item['id']}}">{{$item['method_name']}}</option>
                        @endforeach
                    </select>
                </div>

            </div>
            <div class="col-sm-6">
                <div class="form-group mb-0">
            <div class="" id="method-filed__div">
            </div> </div> </div>
                    <div class="col-sm-6">
                        <div class="form-group mb-0">
                            <label class="form-label" for="ref">{{translate('messages.reference')}}<span class="input-label-secondary"></span></label>
                            <input  class="form-control" type="text" name="ref" id="ref" maxlength="191" placeholder="{{translate('ex_collect_cash')}}">
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="btn--container justify-content-end">
                            <button class="btn btn--reset" type="reset" id="reset_btn">{{translate('messages.reset')}}</button>
                            <button class="btn btn--primary" type="submit">{{translate('messages.save')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header py-2 border-0">
                    <div class="search--button-wrapper">
                        <h5 class="card-title">
                            <span class="card-header-icon">
                                <i class="tio-user"></i>
                            </span>
                            <span>
                                {{ translate('messages.deliverymen_earning_provide_table')}}
                            </span>
                            <span class="badge badge-soft-secondary" id="itemCount">
                                ({{ $provide_dm_earning->total() }})
                            </span>
                        </h5>

                        <form class="search-form">
                        {{-- @csrf --}}
                            <!-- Search -->
                            <div class="input-group input--group">
                                <input id="datatableSearch" name="search" type="search" class="form-control h--40px" placeholder="{{translate('ex_:_search_delivery_man')}}" value="{{ request()?->search ?? null}}" aria-label="{{translate('messages.search_here')}}">
                                <button type="submit" class="btn btn--secondary h--40px"><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>

                        <!-- Unfold -->
                        <div class="hs-unfold mr-2">
                            <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                                data-hs-unfold-options='{
                                        "target": "#usersExportDropdown",
                                        "type": "css-animation"
                                    }'>
                                <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                            </a>

                            <div id="usersExportDropdown"
                                class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                <a id="export-excel" class="dropdown-item" href="{{route('admin.transactions.export-deliveryman-earning', ['type'=>'excel',request()->getQueryString()])}}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                        alt="Image Description">
                                    {{ translate('messages.excel') }}
                                </a>
                                <a id="export-csv" class="dropdown-item" href="{{route('admin.transactions.export-deliveryman-earning', ['type'=>'csv',request()->getQueryString()])}}">
                                    <img class="avatar avatar-xss avatar-4by3 mr-2"
                                        src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                        alt="Image Description">
                                    .{{ translate('messages.csv') }}
                                </a>
                            </div>
                        </div>
                        <!-- End Unfold -->
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{translate('sl')}}</th>
                                    <th class="border-0">{{translate('messages.name')}}</th>
                                    <th class="border-0">{{translate('messages.received_at')}}</th>
                                    <th class="border-0">{{translate('messages.amount')}}</th>
                                    <th class="border-0">{{translate('messages.method')}}</th>
                                    <th class="border-0">{{translate('messages.reference')}}</th>
                                    <th class="border-0 text-center">{{translate('messages.action')}}</th>

                                </tr>
                            </thead>
                            <tbody id="set-rows">
                            @foreach($provide_dm_earning as $k=>$at)
                                <tr>
                                    <td>{{$k+$provide_dm_earning->firstItem()}}</td>
                                    <td>@if($at->delivery_man)<a href="{{route('admin.users.delivery-man.preview', $at->delivery_man_id)}}">{{$at->delivery_man->f_name.' '.$at->delivery_man->l_name}}</a> @else <label class="text-capitalize text-danger">{{translate('messages.deliveryman_deleted')}}</label> @endif </td>
                                    <td>{{\App\CentralLogics\Helpers::time_date_format($at->created_at)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($at['amount'])}}</td>
                                    <td>{{$at['method']}}</td>
                                    @if(  $at['ref'] == 'delivery_man_wallet_adjustment_full')
                                        <td>{{ translate('wallet_adjusted') }}</td>
                                    @elseif( $at['ref'] == 'delivery_man_wallet_adjustment_partial')
                                        <td>{{ translate('wallet_adjusted_partially') }}</td>
                                    @else
                                        <td>{{$at['ref']}}</td>
                                    @endif
                                    <td>
                                            <div class="btn--container justify-content-center"> <a href="#"
                                                data-payment_method="{{ $at->method }}"
                                                data-ref="{{translate($at['ref'])}}"
                                                data-amount="{{\App\CentralLogics\Helpers::format_currency($at['amount'])}}"
                                                data-date="{{\App\CentralLogics\Helpers::time_date_format($at->created_at)}}"
                                                data-type="{{  translate('DeliveryMan_Info') }}"
                                                data-phone="{{  $at?->deliveryman?->phone  }}"
                                                data-address="{{ $at?->deliveryman?->last_location?->location ?? translate('address_not_found') }}"
                                                data-latitude="{{  $at?->deliveryman?->last_location?->location ?? 0 }}"
                                                data-longitude="{{  $at?->deliveryman?->last_location?->location ?? 0 }}"
                                                data-name="{{ $at->delivery_man->f_name.' '.$at->delivery_man->l_name}}"
    
                                                class="btn action-btn btn--warning btn-outline-warning withdraw-info-show" ><i class="tio-visible"></i>
                                                </a>
                                            </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(count($provide_dm_earning) !== 0)
                <hr>
                @endif
                <div class="page-area">
                    {!! $provide_dm_earning->links() !!}
                </div>
                @if(count($provide_dm_earning) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif
            </div>
        </div>
     </div>
</div>
<div class="sidebar-wrap">
    <div class="withdraw-info-sidebar-overlay"></div>
    <div class="withdraw-info-sidebar">
        <div class="d-flex pb-3">
            <span class="circle bg-light withdraw-info-hide cursor-pointer">
                <i class="tio-clear"></i>
            </span>
        </div>

        <div class="d-flex flex-column align-items-center gap-1 mb-4">
            <h3 class="mb-3">{{translate('account_Transaction_Information')}}</h3>
            <div class="d-flex gap-2 align-items-center fs-12">
                <span>{{translate('method')}}:</span>
                <span id="payment_method" class="text-dark font-semibold"></span>
            </div>
            <div class="d-flex gap-2 align-items-center fs-12">
                <span>{{translate('amount')}}:</span>
                <span class="text-dark font-bold" id="amount"> </span>
            </div>
            <div class="d-flex gap-2 align-items-center fs-12">
                <span>{{translate('request_time')}}:</span>
                <span id="date"></span>
            </div>
            <div class="d-flex gap-2 align-items-center fs-12">
                <span>{{translate('reference')}}:</span>
                <span id="ref"></span>
            </div>
        </div>

        <div class="d-flex flex-column align-items-center gap-1 mb-4">

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 font-medium" id="type"></h6>
            </div>
            <div class="card-body">
                <div class="key-val-list d-flex flex-column gap-2" style="--min-width: 60px">
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('name')}}:</span>
                        <span id="name"></span>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('phone')}}:</span>
                        <a href="tel:" id="phone" class="text-dark"></a>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <span>{{translate('address')}}:</span>
                        <a id="address" target="_blank"></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <button id="print-sidebar" class="btn btn-primary ml-auto">
            {{_('طباعه')}}
        </button>
    </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/deliveryman-earning-provide.js"></script>
<script>
    "use strict";
    $('.withdraw-info-hide, .withdraw-info-sidebar-overlay').on('click', function () {
        $('.withdraw-info-sidebar, .withdraw-info-sidebar-overlay').removeClass('show');
    });
    $('.withdraw-info-show').on('click', function () {

let data = $(this).data();
console.log(data)
    $('.sidebar-wrap #payment_method').text(data.payment_method);
    $('.sidebar-wrap #amount').text(data.amount);
    $('.sidebar-wrap #type').text(data.type);
    $('.sidebar-wrap #date').text(data.date);
    $('.sidebar-wrap #ref').text(data.ref);
    $('.sidebar-wrap #name') .text(data.name);
    $('.sidebar-wrap #phone').text(data.phone).attr('href', 'tel:' + data.phone);
    $('.sidebar-wrap #address').text(data.address).attr('href', "https://www.google.com/maps/search/?api=1&query=" + data.latitude + "," + data.longitude);
    // $('#deliverymanReviewModal').modal('show');

    $('.withdraw-info-sidebar, .withdraw-info-sidebar-overlay').addClass('show');

})
$('#print-sidebar').on('click', function () {
    // الحصول على محتوى الشريط الجانبي
    let content = document.querySelector('.withdraw-info-sidebar').innerHTML;

    // إنشاء نافذة جديدة للطباعة
    let printWindow = window.open('', '', 'width=800,height=600');

    printWindow.document.write(`
        <html>
        <head>
            <title>{{ translate('Print') }}</title>
            <!-- تضمين ملفات CSS -->
            <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
            <link rel="stylesheet" href="{{ asset('public/assets/admin/css/vendor.min.css') }}">
            <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
            <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
            <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.min.css') }}">
            <style type="text/css" media="print">
                @page {
                    size: auto;
                    margin: 0; /* إزالة الهوامش */
                }

                body {
                    direction: rtl; /* الكتابة من اليمين لليسار */
                    text-align: right; /* النصوص بمحاذاة اليمين */
                    font-family: 'Arial', sans-serif; /* الخط الافتراضي */
                }

                .withdraw-info-sidebar {
                    position: absolute;
                    top: 0;
                    width: 80mm; /* عرض الورقة */
                    line-height: 1.5;
                    margin: 0 auto;
                    padding: 10px;
                }

                /* إزالة الأزرار والعناصر غير الضرورية */
                .withdraw-info-hide,
                #print-sidebar {
                    display: none;
                }
            </style>
        </head>
        <body>
            ${content} <!-- إدراج محتوى الشريط الجانبي -->
        </body>
        </html>
    `);

    // إغلاق وثيقة الطباعة
    printWindow.document.close();

    // بدء الطباعة
    printWindow.print();
});


    $('#deliveryman').select2({
        ajax: {
            url: '{{url('/')}}/admin/users/delivery-man/get-deliverymen',
            data: function (params) {
                return {
                    q: params.term, // search term
                    earning: true,
                    page: params.page
                };
            },
            processResults: function (data) {
                return {
                results: data
                };
            },
            __port: function (params, success, failure) {
                var $request = $.ajax(params);

                $request.then(success);
                $request.fail(failure);

                return $request;
            }
        }
    });
    $('#withdraw_method').on('change', function () {
    $('#submit_button').attr("disabled","true");
    let method_id = this.value;
console.log(method_id)
    // Set header if need any otherwise remove setup part
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.ajax({
        url: '{{url('/')}}/admin/transactions/account-transaction/method-list/'+this.value,

        data: {
            method_id:method_id,
        },
        processData: false,
        contentType: false,
        type: 'get',
        success: function (response) {
            $('#submit_button').removeAttr('disabled');
            let method_fields = response.content.method_fields;
            $("#method-filed__div").html("");
            method_fields.forEach((element, index) => {
                $("#method-filed__div").append(`
                    <div class="form-group mt-2">
                        <label for="wr_num" class="form-label">${element.input_name.replaceAll('_', ' ')}</label>
                        <input type="${element.input_type == 'phone' ? 'number' : element.input_type  }" class="form-control" name="${element.input_name}" placeholder="${element.placeholder}" ${element.is_required === 1 ? 'required' : ''}>
                    </div>
                `);
            })

        },
        error: function () {

        }
    });
});
    $('#add_transaction').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.post({
            url: '{{route('admin.transactions.provide-deliveryman-earnings.store')}}',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                if (data.errors) {
                    for (var i = 0; i < data.errors.length; i++) {
                        toastr.error(data.errors[i].message, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                } else {
                    toastr.success('{{translate('messages.transaction_saved')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    setTimeout(function () {
                        location.href = '{{route('admin.transactions.provide-deliveryman-earnings.index')}}';
                    }, 2000);
                }
            } 
        });
    });

    function getAccountData(route, data_id, type)
    {
        $.get({
                url: route+data_id,
                dataType: 'json',
                success: function (data) {
                    $('#account_info').html('({{translate('messages.cash_in_hand')}}: '+data.cash_in_hand+' {{translate('messages.balance')}}: '+data.earning_balance+')');
                },
            });
    }
</script>
@endpush
