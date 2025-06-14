<div class="content container-fluid invoice-page initial-38">
    <div id="printableArea">
        <div>
            <div class="text-center">
                <input type="button" class="btn btn-primary mt-3 non-printable" onclick="printDiv('printableArea')"
                    value="{{ translate('Proceed,_If_thermal_printer_is_ready.') }}" />
                <a href="{{ url()->previous() }}"
                    class="btn btn-danger non-printable mt-3">{{ translate('messages.back') }}</a>
            </div>

            <hr class="non-printable">
            <div class="print--invoice initial-38-1">
                @if ($order->store)
                    <div class="text-center pt-4 mb-3">
                        <img class="invoice-logo" src="{{ asset('/public/assets/admin/img/invoice-logo.png') }}"
                            alt="">
                        <div class="top-info">
                            <h2 class="store-name">{{ $order->store->name }}</h2>
                            <div>
                                {{ $order->store->address }}
                            </div>
                            <div class="mt-1 d-flex justify-content-center">
                                <span>{{ translate('messages.phone') }}</span> <span>:</span> <span>{{ $order->store->phone }}</span>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="top-info">
                    <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="" class="w-100">
                    <div class="text-uppercase text-center">{{ translate('messages.cash_receipt') }}</div>
                    <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="" class="w-100">
                </div>
                <div class="order-info-id text-center">
                    <h5 class="d-flex justify-content-center"><span>{{ translate('order_id') }}</span> <span>:</span> <span>{{ $order['id'] }}</span></h5>
                    <div>
                        {{ date('d/M/Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                    </div>
                    <div>
                        @if ($order->store?->gst_status)
                            <span>{{ translate('Gst No') }}</span> <span>:</span> <span>{{ $order->store->gst_code }}</span>
                    @endif
                    </div>
                </div>
                <div class="order-info-details">
                    <div class="row mt-3">
                        @if ($order->order_type == 'parcel')
                            <div class="col-12">
                                @php($address = json_decode($order->delivery_address, true))
                                <h5>{{ translate('messages.sender_info') }}</h5>
                                <div class="d-flex">
                                    <span>{{ translate('messages.sender_name') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['contact_person_name'] : $order->address['f_name'] . ' ' . $order->customer['l_name'] }}</span>
                                </div>
                                <div class="d-flex">
                                    <span>{{ translate('messages.phone') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['contact_person_number'] : $order->customer['phone'] }}</span>
                                </div>
                                <div class="text-break d-flex">
                                    <span class="word-nobreak">{{ translate('messages.address') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['address'] : '' }}</span>
                                </div>
                                @php($address = $order->receiver_details)
                                <h5><u>{{ translate('messages.receiver_info') }}</u></h5>
                                <div class="d-flex">
                                    <span>{{ translate('messages.receiver_name') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['contact_person_name'] : $order->address['f_name'] . ' ' . $order->customer['l_name'] }}</span>
                                </div>
                                <div class="d-flex">
                                    <span>{{ translate('messages.phone') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['contact_person_number'] : $order->customer['phone'] }}</span>
                                </div>
                                <div class="text-break d-flex">
                                    <span class="word-nobreak">{{ translate('messages.address') }}</span> <span>:</span>
                                    <span>{{ isset($address) ? $address['address'] : '' }}</span>
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                @php($address = json_decode($order->delivery_address, true))
                                @if (!empty($address))
                                    <h5 class="d-flex">
                                        <span>{{ translate('messages.contact_name') }}</span> <span>:</span>
                                        <span>{{ isset($address['contact_person_name']) ? $address['contact_person_name'] : '' }}</span>
                                    </h5>
                                    <h5 class="d-flex">
                                        <span>{{ translate('messages.phone') }}</span> <span>:</span>
                                        <span>{{ isset($address['contact_person_number']) ? $address['contact_person_number'] : '' }}</span>
                                    </h5>
                                    <h5 class="text-break d-flex">
                                        <span class="word-nobreak">{{ translate('messages.address') }}</span> <span>:</span>
                                        <span>{{ isset($address['address']) ? $address['address'] : '' }}</span>
                                    </h5>
                                    @elseif ($order->customer)
                                    <h5 class="d-flex">
                                        <span>{{ translate('messages.contact_name') }}</span> <span>:</span>
                                        <span>{{ $order->customer?->f_name .' '.$order->customer?->l_name }}</span>
                                    </h5>
                                    <h5 class="d-flex">
                                        <span>{{ translate('messages.phone') }}</span> <span>:</span>
                                        <span>{{ $order->customer?->phone}}</span>
                                    </h5>
                                @endif
                            </div>
                        @endif
                    </div>
                    <tablez class="table invoice--table text-black mt-3">
                        <thead class="border-0">
                            <tr class="border-0">
                                <th>{{ translate('messages.desc') }}</th>
                                <th class="w-10p"></th>
                                <th>{{ translate('messages.price') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @if ($order->order_type == 'parcel')
                                <tr>
                                    <td>{{ translate('messages.delivery_charge') }}</td>
                                    <td class="text-center">1</td>
                                    <td>{{ \App\CentralLogics\Helpers::format_currency($order->delivery_charge) }}</td>
                                </tr>
                            @else
                                @php($sub_total = 0)
                                <?php
                                if ($order->prescription_order == 1) {
                                    $sub_total = $order['order_amount'] - $order['delivery_charge'] - $order['total_tax_amount'] - $order['dm_tips'] + $order['store_discount_amount'];
                                }
                                ?>
                                @php($total_tax = 0)
                                @php($total_dis_on_pro = 0)
                                @php($add_ons_cost = 0)
                                @foreach ($order->details as $detail)
                                    @php($item = json_decode($detail->item_details, true))
                                    <tr>
                                        <td class="text-break">
                                            {{ $item['name'] }} <br>
                                            @if ($order->store && $order->store->module->module_type == 'food')
                                                @if (count(json_decode($detail['variation'], true)) > 0)
                                                    <strong><u>{{ translate('messages.variation') }} : </u></strong>
                                                    @foreach (json_decode($detail['variation'], true) as $variation)
                                                        @if (isset($variation['name']) && isset($variation['values']))
                                                            <span class="d-block text-capitalize">
                                                                <strong>{{ $variation['name'] }} - </strong>
                                                            </span>
                                                            @foreach ($variation['values'] as $value)
                                                                <span class="d-block text-capitalize">
                                                                    &nbsp; &nbsp; {{ $value['label'] }} :
                                                                    <strong>{{ \App\CentralLogics\Helpers::format_currency($value['optionPrice']) }}</strong>
                                                                </span>
                                                            @endforeach
                                                        @else
                                                            @if (isset(json_decode($detail['variation'], true)[0]))
                                                                @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                                    <div class="font-size-sm text-body">
                                                                        <span>{{ $key1 }} : </span>
                                                                        <span
                                                                            class="font-weight-bold">{{ $variation }}</span>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        @break
                                                    @endif
                                                @endforeach
                                            @endif
                                        @else
                                            @if (count(json_decode($detail['variation'], true)) > 0)
                                                <strong><u>Variation : </u></strong>
                                                @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                    @if ($key1 != 'stock')
                                                        <div class="font-size-sm text-body">
                                                            <span>{{ $key1 }} : </span>
                                                            <span
                                                                class="font-weight-bold">{{ $key1 == 'price' ? \App\CentralLogics\Helpers::format_currency($variation) : $variation }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @endif
                                        <div class="addons">
                                            @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                                @if ($key2 == 0)
                                                    <strong><u>{{ translate('messages.addons') }} :
                                                        </u></strong>
                                                @endif
                                                <div>
                                                    <span class="text-break">{{ $addon['name'] }} : </span>
                                                    <span class="font-weight-bold">
                                                        {{ $addon['quantity'] }} x
                                                        {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                    </span>
                                                </div>
                                                @php($add_ons_cost += $addon['price'] * $addon['quantity'])
                                            @endforeach
                                        </div>
                                        @if (count(json_decode($detail['variation'], true)) <= 0)
                                            <div class="price">
                                                {{ \App\CentralLogics\Helpers::format_currency($detail->price) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $detail['quantity'] }}
                                    </td>
                                    <td class="w-28p">
                                        @php($amount = $detail['price'] * $detail['quantity'])
                                        {{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                    </td>
                                </tr>
                                @php($sub_total += $amount)
                                @php($total_tax += $detail['tax_amount'] * $detail['quantity'])
                            @endforeach
                        @endif

                    </tbody>
                </table>
                <div class="checkout--info">
                    <dl class="row text-right">
                        @if ($order->order_type != 'parcel')
                            <dt class="col-6">{{ translate('messages.subtotal') }}
                                @if ($order->tax_status == 'included' )
                                    ({{ translate('messages.TAX_Included') }})
                                    @endif
                                :</dt>
                            <dd class="col-6">
                                {{ \App\CentralLogics\Helpers::format_currency($sub_total + $add_ons_cost) }}</dd>
                            <dt class="col-6">{{ translate('messages.discount') }}:</dt>
                            <dd class="col-6">
                                -
                                {{ \App\CentralLogics\Helpers::format_currency($order['store_discount_amount'] + $order['flash_admin_discount_amount']  + $order['flash_store_discount_amount']) }}
                            </dd>


                            <dt class="col-6">{{ translate('messages.coupon_discount') }}:</dt>
                            <dd class="col-6">
                                -
                                {{ \App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount']) }}
                            </dd>
                            @if ($order['ref_bonus_amount'] > 0)
                            <dt class="col-6">{{ translate('messages.Referral_Discount') }}:</dt>
                            <dd class="col-6">
                                -
                                {{ \App\CentralLogics\Helpers::format_currency($order['ref_bonus_amount']) }}
                            </dd>
                            @endif
                            @if ($order->tax_status == 'excluded' || $order->tax_status == null  )
                            <dt class="col-6">{{ translate('messages.vat/tax') }}:</dt>
                            <dd class="col-6">+
                                {{ \App\CentralLogics\Helpers::format_currency($order['total_tax_amount']) }}</dd>
                            @endif
                            <dt class="col-6">{{ translate('messages.delivery_man_tips') }}:</dt>
                            <dd class="col-6">
                                @php($delivery_man_tips = $order['dm_tips'])
                                + {{ \App\CentralLogics\Helpers::format_currency($delivery_man_tips) }}
                            </dd>
                            <dt class="col-6">{{ translate('messages.delivery_charge') }}:</dt>
                            <dd class="col-6">
                                @php($del_c = $order['delivery_charge'])
                                {{ \App\CentralLogics\Helpers::format_currency($del_c) }}
                            </dd>
                        @else
                            <dt class="col-6">{{ translate('messages.delivery_man_tips') }}:</dt>
                            <dd class="col-6">
                                @php($delivery_man_tips = $order['dm_tips'])
                                + {{ \App\CentralLogics\Helpers::format_currency($delivery_man_tips) }}
                            </dd>
                            @endif
                            <dt class="col-6">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}:</dt>
                            <dd class="col-6">
                                @php($additional_charge = $order['additional_charge'])
                                + {{ \App\CentralLogics\Helpers::format_currency($additional_charge) }}
                            </dd>

                            @if ($order['extra_packaging_amount'] > 0)
                            <dt class="col-6">{{ translate('messages.Extra_Packaging_Amount') }}:</dt>
                            <dd class="col-6">
                                +
                                {{ \App\CentralLogics\Helpers::format_currency($order['extra_packaging_amount']) }}
                            </dd>
                            @endif
                        <dt class="col-6 total">{{ translate('messages.total') }}:</dt>
                        <dd class="col-6 total">
                            {{ \App\CentralLogics\Helpers::format_currency($order->order_amount) }}</dd>
                            @if ($order?->payments)
                                @foreach ($order?->payments as $payment)
                                    @if ($payment->payment_status == 'paid')
                                        @if ( $payment->payment_method == 'cash_on_delivery')

                                        <dt class="col-6 text-left">{{ translate('messages.Paid_with_Cash') }} ({{  translate('COD')}}) :</dt>
                                        @else

                                        <dt class="col-6 text-left">{{ translate('messages.Paid_by') }} {{  translate($payment->payment_method)}} :</dt>
                                        @endif
                                    @else

                                    <dt class="col-6 text-left">{{ translate('Due_Amount') }} ({{  $payment->payment_method == 'cash_on_delivery' ?  translate('messages.COD') : translate($payment->payment_method) }}) :</dt>
                                    @endif
                                <dd class="col-6 ">
                                    {{ \App\CentralLogics\Helpers::format_currency($payment->amount) }}
                                </dd>
                                @endforeach
                            @endif

                    </dl>
                    @if ($order->payment_method != 'cash_on_delivery')
                        <div class="d-flex flex-row justify-content-between border-top">
                            <span class="d-flex">
                                <span>{{ translate('messages.Paid by') }}</span> <span>:</span>
                                <span>{{ translate('messages.' . $order->payment_method) }}</span> </span>
                            <span> <span>{{ translate('messages.amount') }}</span> <span>:</span>
                                <span>{{ $order->adjusment + $order->order_amount }}</span> </span>
                            <span> <span>{{ translate('messages.change') }}</span> <span>:</span> <span>{{ abs($order->adjusment) }}</span> </span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="top-info mt-2">
                <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="" class="w-100">
                <div class="text-uppercase text-center">{{ translate('THANK YOU') }}</div>
                <img src="{{ asset('/public/assets/admin/img/invoice-star.png') }}" alt="" class="w-100">
                <div class="copyright">
                    &copy; {{ \App\Models\BusinessSetting::where(['key' => 'business_name'])->first()->value }}.
                    <span
                        class="d-none d-sm-inline-block">{{ \App\Models\BusinessSetting::where(['key' => 'footer_text'])->first()->value }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
