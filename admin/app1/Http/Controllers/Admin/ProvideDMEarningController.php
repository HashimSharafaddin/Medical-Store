<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusinessSetting;
use App\Models\DeliveryMan;
use App\Models\Disbursement;
use App\Models\DisbursementDetails;
use App\Models\Expense;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\ProvideDMEarning;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\DeliverymanPaymentExport;
use Illuminate\Support\Facades\Validator;

class ProvideDMEarningController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $provide_dm_earning = ProvideDMEarning::when(isset($key), function ($query) use ($key) {
            return $query->whereHas('delivery_man',function($query)use($key){
                foreach ($key as $value) {
                    $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                }
            });
        })->latest()->paginate(config('default_pagination'));
        return view('admin-views.deliveryman-earning-provide.index', compact('provide_dm_earning'));
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
            'deliveryman_id' => 'required',
            'method'=>'max:191',
            'ref'=>'max:191',
            'amount' => 'required',
        ]);


        $dm = DeliveryMan::findOrFail($request['deliveryman_id']);
        $method = WithdrawalMethod::ofStatus(1)->where('id',  $request['withdraw_method'])->first();
        $fields = array_column($method->method_fields, 'input_name');
        $values = $request->all();

        $method_data = [];
        foreach ($fields as $field) {
            if(key_exists($field, $values)) {
                $method_data[$field] = $values[$field];
            }
        }
        $current_balance = $dm->wallet?$dm->wallet->total_earning - $dm->wallet->total_withdrawn:0;

        if (round($current_balance,2) < round($request['amount'],2)) {
            $validator->getMessageBag()->add('amount', 'Insufficient balance!');
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $amount =0;
         $type= 'سند صرف';
if($request['pay_type']==1){
    $amount=  $request['amount'];
    $type= 'سند قبض';

}else{
    $amount = $request['amount'] -$request['amount']-$request['amount'] ;
    $type= 'سند صرف';

}
        $provide_dm_earning = new ProvideDMEarning();

        $provide_dm_earning->delivery_man_id = $dm->id;
        $provide_dm_earning->method = $method['method_name'];
        $provide_dm_earning->ref = $request['ref'];
        $provide_dm_earning->amount = $amount;
     
        if($request['pay_type']!=1 ){
        
            // $type= 'قبض';
        
        
            if($request['pay_type']!=1 ){
        
                // $type= 'سند قبض';
            
            
            $expense = new Expense();
            $expense->amount =  $request['amount'];
            $expense->type = $type;
            $expense->order_id = $dm->id;
            $expense->created_by = 'admin';
            // if($request['type']=='store' && $request['store_id'] )
            // {
            // $expense->store_id = $data->id;
            // }else if($request['type']=='deliveryman' && $request['deliveryman_id'] ){
                $expense->delivery_man_id = $$dm->id;
    
            // }else if($request['type']=='customer' && $request['customer_id'] ){
            //     $expense->user_id = $data->id;
    
            // }
            $expense->method_id = $method['id'];
    
            $expense->description = $request['ref'];
            $expense->created_at = now();
            $expense->updated_at = now();
            $expense->save();

            $data1 = [
                'delivery_man_id' => $dm['id'],
                'withdrawal_method_id' => $method['id'],
                'method_name' => $method['method_name'],
                'method_fields' => json_encode($method_data),
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];
    
            DB::table('disbursement_withdrawal_methods')->insert($data1);

            }
        }
        try
        {
            DB::beginTransaction();
            $provide_dm_earning->save();
            $dm->wallet->increment('total_withdrawn', $amount);
            DB::commit();

            $disbursement = new Disbursement();
            $disbursement->id = 1000 + Disbursement::count() + 1;
            if (Disbursement::find($disbursement->id)) {
                $disbursement->id = Disbursement::orderBy('id', 'desc')->first()->id + 1;
            }
            $disbursement->title = $method['method_name'].$disbursement->id;
            $disbursement->status = 'completed';
            $minimum_amount = BusinessSetting::where(['key' => 'dm_disbursement_min_amount'])->first()?->value;
            // foreach ($delivery_mans as $delivery_man){
                // if(isset($dm->wallet)){
    
                  
    
                    // if ($disbursement_amount>$minimum_amount && $dm->disbursement_method){
                        $res_d = [
                            'disbursement_id' => $disbursement->id,
                            'delivery_man_id' => $dm->id,
                            'disbursement_amount' => $amount,
                            'payment_method' => $dm->disbursement_method->id,
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
            // }
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return response()->json(['error'=>$e],200);
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
        DeliveryMan::where('id', $id)->delete();
        Toastr::success(translate('messages.provided_dm_earnings_removed'));
        return back();
    }

    public function dm_earning_list_export(Request $request){
        $key = isset($request['search']) ? explode(' ', $request['search']) : [];
        $dm_earnings = ProvideDMEarning::when(isset($key), function ($query) use ($key) {
            return $query->whereHas('delivery_man',function($query)use($key){
                foreach ($key as $value) {
                    $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
                }
            });
        })->latest()->get();

        $data = [
            'dm_earnings'=>$dm_earnings,
            'search'=>$request->search??null,

        ];
        
        if ($request->type == 'excel') {
            return Excel::download(new DeliverymanPaymentExport($data), 'ProvideDMEarning.xlsx');
        } else if ($request->type == 'csv') {
            return Excel::download(new DeliverymanPaymentExport($data), 'ProvideDMEarning.csv');
        }
    }

    public function search_deliveryman_earning(Request $request){
        $key = explode(' ', $request['search']);
        $provide_dm_earning = ProvideDMEarning::with('delivery_man')->whereHas('delivery_man',function($query)use($key){
            foreach ($key as $value) {
                $query->where('f_name', 'like', "%{$value}%")->orWhere('l_name', 'like', "%{$value}%");
            }
        })->get();

        return response()->json([
            'view'=>view('admin-views.deliveryman-earning-provide.partials._table', compact('provide_dm_earning'))->render(),
            'total'=>$provide_dm_earning->count()
        ]);
    }
}
