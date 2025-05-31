<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\CustomerLogic;
use App\Models\Admin;
use App\Models\DeliveryManWallet;
use App\Models\Disbursement;
use App\Models\DisbursementDetails;
use App\Models\Expense;
use App\Models\Store;
use App\Models\AdminWallet;
use App\Models\DeliveryMan;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Exports\CollectCashTransactionExport;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Validator;

class AccountTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $account_transaction = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        })
            ->latest()->paginate(config('default_pagination'));
        return view('admin-views.account.index', compact('account_transaction'));
    }
    public function method_list(Request $request,$id)
    {
        $method_id = $request->input('method_id');
        $method = WithdrawalMethod::ofStatus(1)->where('id',  $id)->first();

        return response()->json(['content'=>$method], 200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:store,deliveryman',
            'method' => 'required',
            'store_id' => 'required_if:type,store',
            'deliveryman_id' => 'required_if:type,deliveryman',
            'amount' => 'required|numeric',
        ]);

        if ($request['store_id'] && $request['deliveryman_id']) {
            $validator->getMessageBag()->add('from type', 'Can not select both deliveryman and store');
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $method = WithdrawalMethod::ofStatus(1)->where('id',  $request['withdraw_method'])->first();

        if($request['type']=='store' && $request['store_id']&&  $method )
        {
            $store = Store::findOrFail($request['store_id']);
            $data = $store->vendor;
            $current_balance = $data->wallet?$data->wallet->collected_cash:0;
        }
        else if($request['type']=='deliveryman' && $request['deliveryman_id']&&  $method )
        {
            $data = DeliveryMan::findOrFail($request['deliveryman_id']);

            if( !$data->wallet){
                $wallet = new DeliveryManWallet();
                $wallet->delivery_man_id =    $data->id;
                $wallet->save();

            }
            $current_balance = $data->wallet?$data->wallet->collected_cash:0;
        }else if($request['type']=='customer' && $request['customer_id']&&  $method )
        {
            $data = User::find($request['customer_id']);

            // if( !$data->wallet){
            //     $wallet = new DeliveryManWallet();
            //     $wallet->delivery_man_id =    $data->id;
            //     $wallet->save();

            // }
        
            $current_balance = $data->balance + $request['amount'];
            $data->balance =  $current_balance  ;
            $data->save();
        }
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if(key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }
        // if ($current_balance < $request['amount']) {
        //     $validator->getMessageBag()->add('amount', translate('messages.insufficient_balance'));
        //     return response()->json(['errors' => Helpers::error_processor($validator)]);
        // }

        // if ($validator->fails()) {
        //     return response()->json(['errors' => Helpers::error_processor($validator)]);
        // }
        $amount =0;
        $type= '';
if($request['pay_type']==1&& $request['type']=='customer'){
    $type= 'سند صرف';
    $amount = $request['amount'] -$request['amount']-$request['amount'] ;


} else if($request['pay_type']==0 && $request['type']=='customer') {
    $amount=  $request['amount'];

    $type= 'سند قبض';

}

if($request['pay_type']==1&& $request['type']=='store'){
    $type= 'سند صرف';
    $amount = $request['amount'] -$request['amount']-$request['amount'] ;


} else if($request['pay_type']==0 && $request['type']=='store') {
    $amount=  $request['amount'];

    $type= 'سند قبض';

}

if($request['pay_type']==0 && $request['type']=='deliveryman'){
    $amount=  $request['amount'];

    $type= 'سند قبض';


} else if($request['pay_type']==1 && $request['type']=='deliveryman') {
    $amount = $request['amount'] -$request['amount']-$request['amount'] ;

    $type= 'سند صرف';

}
        $account_transaction = new AccountTransaction();
        $account_transaction->from_type = $request['type'];
        $account_transaction->type = $request['type'];

        $account_transaction->from_id = $data->id;
        $account_transaction->method = $method['method_name'];
        $account_transaction->method_id = $method['id'];

        $account_transaction->ref = $request['ref'];
        $account_transaction->amount =  $amount;
        $account_transaction->type_pay = $type;
        $account_transaction->current_balance = $current_balance;
        if( $request['type']=='store'){
        $data1 = [
            'vendor_id' => $data->id,
            'amount' => $request['amount'],
            'transaction_note' =>$request['ref'],
            'withdrawal_method_id' => $method['id'],
            'withdrawal_method_fields' => json_encode($method_data),
            'approved' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('withdraw_requests')->insert($data1);
    }
        if($request['pay_type']!=0 ){
        
            // $type= 'سند قبض';
            if( $request['type']=='deliveryman'){

            $data1 = [
                'delivery_man_id' => $data['id'],
                'withdrawal_method_id' => $method['id'],
                'method_name' => $method['method_name'],
                'method_fields' => json_encode($method_data),
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];
    
            DB::table('disbursement_withdrawal_methods')->insert($data1);
            $disbursement = new Disbursement();
            $disbursement->id = 1000 + Disbursement::count() + 1;
            if (Disbursement::find($disbursement->id)) {
                $disbursement->id = Disbursement::orderBy('id', 'desc')->first()->id + 1;
            }
            $disbursement->title = $method['method_name'].$disbursement->id;
            $disbursement->status = 'completed';
            // $minimum_amount = BusinessSetting::where(['key' => 'dm_disbursement_min_amount'])->first()?->value;
            // foreach ($delivery_mans as $delivery_man){
                // if(isset($dm->wallet)){
    
                  
    
                    // if ($disbursement_amount>$minimum_amount && $dm->disbursement_method){
                        $res_d = [
                            'disbursement_id' => $disbursement->id,
                            'delivery_man_id' => $data->id,
                            'disbursement_amount' => $amount,
                            'payment_method' => $method['id'],
                            'status'=>'completed',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        $disbursement_details[] = $res_d;
                        // $total_amount += $res_d['disbursement_amount'];
                        // $dm->wallet->pending_withdraw = $dm->wallet->pending_withdraw + $disbursement_amount;
                        // $dm->wallet->save();
                    // }
                // }
    
            // }
    
            // if ($disbursement_amount > 0){
                $disbursement->total_amount =  $amount;
                $disbursement->created_for = 'delivery_man';
                $disbursement->save();
    
                DisbursementDetails::insert($disbursement_details);
        }
        $expense = new Expense();
        $expense->amount =  $request['amount'];
        $expense->type = $type;
        $expense->order_id = $account_transaction->id;
        $expense->created_by = 'admin';
        if($request['type']=='store' && $request['store_id'] )
        {
        $expense->store_id = $data->id;
        }else if($request['type']=='deliveryman' && $request['deliveryman_id'] ){
            $expense->delivery_man_id = $data->id;

        }else if($request['type']=='customer' && $request['customer_id'] ){
            $expense->user_id = $data->id;

        }
        $expense->method_id = $method['id'];

        $expense->description = $request['ref'];
        $expense->created_at = now();
        $expense->updated_at = now();
        $expense->save();
        }
        if($request['type']=='customer'){
          CustomerLogic::create_wallet_transaction($data->id, $amount, 'add_fund_by_admin',$request->ref);

        
        
        }
        try
        {
            DB::beginTransaction();
            $account_transaction->save();
            if($request['type']!='customer'){
                $data->wallet->decrement('collected_cash', $amount );

            }
            AdminWallet::where('admin_id', Admin::where('role_id', 1)->first()->id)->increment('manual_received', $request['amount']);
            // Helpers::expenseCreate($amount, $type,now(),'admin',$account_transaction->id,$account_transaction->id,0,$request['ref'],$data->id,$data->id);
            DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return $e;
        }

        try {

            if( $request['type'] == 'deliveryman' && $request['deliveryman_id'] &&   Helpers::getNotificationStatusData('deliveryman','deliveryman_collect_cash','push_notification_status') && $data->fcm_token){
                $notification_data = [
                    'title' => translate('messages.Cash_Collected'),
                    'description' => translate('messages.Your_hand_in_cash_has_been_collected_by_admin'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'cash_collect'
                ];
                Helpers::send_push_notif_to_device($data->fcm_token, $notification_data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($notification_data),
                    'delivery_man_id' => $data->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            if($request['type']=='deliveryman' && $request['deliveryman_id'] && config('mail.status') &&  Helpers::get_mail_status('cash_collect_mail_status_dm') == '1'  &&  Helpers::getNotificationStatusData('deliveryman','deliveryman_collect_cash','mail_status')){
                Mail::to($data['email'])->send(new \App\Mail\CollectCashMail($account_transaction,$data['f_name']));
            }
        } catch (\Throwable $th) {

        }
        return response()->json(200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $account_transaction=AccountTransaction::findOrFail($id);
        return view('admin-views.account.view', compact('account_transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        AccountTransaction::where('id', $id)->delete();
        Toastr::success(translate('messages.account_transaction_removed'));
        return back();
    }

    public function export_account_transaction(Request $request){
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $account_transaction = AccountTransaction::
        when(isset($key), function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('ref', 'like', "%{$value}%");
                }
            });
        }) ->where('type', 'collected')
            ->latest()->get();

        $data = [
            'account_transactions'=>$account_transaction,
            'search'=>$request->search??null,

        ];

        if ($request->type == 'excel') {
            return Excel::download(new CollectCashTransactionExport($data), 'CollectCashTransactions.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new CollectCashTransactionExport($data), 'CollectCashTransactions.csv');
        }
    }

    public function search_account_transaction(Request $request){
        $key = explode(' ', $request['search']);
        $account_transaction = AccountTransaction::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('ref', 'like', "%{$value}%");
            }
        })
            ->where('type', 'collected' )
            ->get();

        return response()->json([
            'view'=>view('admin-views.account.partials._table', compact('account_transaction'))->render(),
            'total'=>$account_transaction->count()
        ]);
    }
}
