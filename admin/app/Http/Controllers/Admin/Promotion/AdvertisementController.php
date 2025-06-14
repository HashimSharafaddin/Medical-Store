<?php

namespace App\Http\Controllers\Admin\Promotion;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Advertisement;
use App\Rules\WordValidation;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\AdvertisementStoreRequest;
use App\Http\Requests\Admin\AdvertisementUpdateRequest;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $key = explode(' ', $request['search']);
        $adds=Advertisement::where('is_updated',0)
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->whereNotIn('status' ,['pending','denied' ])

        ->when($request?->ads_type === 'running',function($query){
            $query->valid();
        })
        ->when($request?->ads_type === 'approved',function($query){
            $query->approved();
        })
        ->when($request?->ads_type === 'expired',function($query){
            $query->expired();
        })
        ->when($request?->ads_type === 'paused',function($query){
            $query->where('status','paused');
        })
        ->when($request?->search ,function($query)use($key) {
            foreach ($key as $value) {
            $query->where(function($query) use ($value){
                    $query->where('id', 'like', "%{$value}%")->orWhereHas('store',function($query)use ($value){
                        $query->where('name', 'like', "%{$value}%");
                    });
                });
            };
        })
        ->orderByRaw('ISNULL(priority), priority ASC')
        ->paginate(config('default_pagination'));


        $total_adds=Advertisement::whereNotNull('priority')
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->count() + 1;
        $ads_count= Advertisement::when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->count();


        return view("admin-views.advertisement.list",compact('adds','total_adds','ads_count'));
    }

    public function requestList(Request $request)
    {
        $key = explode(' ', $request['search']);

        $adds=Advertisement::
        when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->when(!$request?->type ,function($query)use($request,$key){
            $query->where('is_updated' ,0)->whereIn('status' ,['pending'])->when($request?->search ,function($query)use($key) {
                foreach ($key as $value) {
                $query->where(function($query) use ($value){
                        $query->where('id', 'like', "%{$value}%")->orWhereHas('store',function($query)use ($value){
                            $query->where('name', 'like', "%{$value}%");
                        });
                    });
                };
            });
        })



        ->when($request?->type === 'update-requests',function($query)use($request,$key){
            $query->where('is_updated' ,1)->whereIn('status' ,['pending'])      ->when($request?->search ,function($query)use($key) {
                foreach ($key as $value) {
                $query->where(function($query) use ($value){
                        $query->where('id', 'like', "%{$value}%")->orWhereHas('store',function($query)use ($value){
                            $query->where('name', 'like', "%{$value}%");
                        });
                    });
                };
            });
        })
        ->when($request?->type == 'denied-requests',function($query)use($request,$key){
            $query->whereIn('status' ,['denied'])      ->when($request?->search ,function($query)use($key) {
                foreach ($key as $value) {
                $query->where(function($query) use ($value){
                        $query->where('id', 'like', "%{$value}%")->orWhereHas('store',function($query)use ($value){
                            $query->where('name', 'like', "%{$value}%");
                        });
                    });
                };
            });
        })


        ->paginate(config('default_pagination'));
        $type= $request?->type;
        $count=Advertisement::whereIn('status' ,['pending','denied' ])
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->count();
        return view("admin-views.advertisement.request-list",compact('adds','count','type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $total_adds=Advertisement::whereNotNull('priority')
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->count()+1;
        return view("admin-views.advertisement.create",compact('total_adds','defaultLang','language'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(AdvertisementStoreRequest $request)
    {
        $dateRange = $request->dates;
        list($startDate, $endDate) = explode(' - ', $dateRange);
        $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($startDate));
        $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($endDate));
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();


        $newPriority = $request['priority'];
        $request['priority'] > 0 ? Advertisement::
        when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->where('priority', '>=', $newPriority)->increment('priority') : null;

        $advertisement = New Advertisement();
        $advertisement->store_id = $request->store_id;
        $advertisement->add_type = $request->advertisement_type;
        $advertisement->title = $request->title[array_search('default', $request->lang)];
        $advertisement->description = $request->description[array_search('default', $request->lang)];
        $advertisement->start_date = $startDate;
        $advertisement->end_date = $endDate;
        $advertisement->priority = $newPriority;
        $advertisement->is_rating_active = $request->advertisement_type == 'store_promotion' ?  $request?->rating ?? 0 : 0;
        $advertisement->is_review_active = $request->advertisement_type == 'store_promotion' ?  $request?->review ?? 0 : 0;
        $advertisement->is_paid =  1 ;
        $advertisement->created_by_id = auth('admin')->id();
        $advertisement->created_by_type = 'App\Models\Admin';
        $advertisement->status = 'approved';

        $advertisement->cover_image = $request->has('cover_image') &&  $request->advertisement_type == 'store_promotion' ?  Helpers::upload(dir: 'advertisement/', format:$request->file('cover_image')->getClientOriginalExtension(), image:$request->file('cover_image')) : null;
        $advertisement->profile_image = $request->has('profile_image') &&  $request->advertisement_type == 'store_promotion' ?  Helpers::upload(dir: 'advertisement/', format:$request->file('profile_image')->getClientOriginalExtension(), image:$request->file('profile_image')) : null;
        $advertisement->video_attachment = $request->has('video_attachment') &&  $request->advertisement_type == 'video_promotion' ?  Helpers::upload(dir: 'advertisement/', format:$request->file('video_attachment')->getClientOriginalExtension(), image:$request->file('video_attachment')) : null;
        $advertisement->save();


        $advertisement->module_id= $advertisement->store?->module?->id;
        $advertisement->module_type= $advertisement->store?->module?->module_type;
        $advertisement->save();


        Helpers::add_or_update_translations(request: $request, key_data:'title' , name_field:'title' , model_name: 'Advertisement' ,data_id: $advertisement->id,data_value: $advertisement->title);

        Helpers::add_or_update_translations(request: $request, key_data:'description' , name_field:'description' , model_name: 'Advertisement' ,data_id: $advertisement->id,data_value: $advertisement->description);
        try {

            if( Helpers::getNotificationStatusData('store','store_advertisement_create_by_admin','push_notification_status' ,$advertisement?->store?->id) && $advertisement?->store?->vendor?->firebase_token ){

                $data = [
                    'title' => translate('New_Advertisement'),
                    'description' => translate('Admin_has_added_a_new_advertisement_for_your_store'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'advertisement',
                    'advertisement_id' => $advertisement->id,
                    'order_status' => '',
                ];
                Helpers::send_push_notif_to_device($advertisement->store->vendor->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $advertisement->store->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            if(Helpers::getNotificationStatusData('store','store_advertisement_create_by_admin','mail_status',$advertisement?->store?->id) &&  config('mail.status') && Helpers::get_mail_status('advertisement_create_mail_status_store') == '1'){
                Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,'advertisement_create' ,$advertisement->id));
            }
        } catch (\Throwable $th) {
        }

        return response()->json(['type'=> 'admin' ,'message'=>translate('messages.Advertisement_Added_Successfully') ], 200);

    }


    /**
     * Display the specified resource.
     */
    public function show($advertisement,Request $request)
    {
        $request_page_type=$request?->request_page_type ?? null;
        $nextId = Advertisement::where('id', '>', $advertisement)
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->when($request_page_type == 'update-requests' , function($query){
            $query->where('is_updated',1)->whereNotIn('status' ,['pending']);
        })
        ->when($request_page_type == 'denied-requests' , function($query){
            $query->whereIn('status' ,['denied']);
        })
        ->when($request_page_type == 'pending-requests' , function($query){
            $query->where('is_updated',0)->whereIn('status' ,['pending']);
        })
        ->min('id');
        $previousId = Advertisement::where('id', '<', $advertisement)
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->when($request_page_type == 'update-requests' , function($query){
            $query->where('is_updated',1)->whereNotIn('status' ,['pending']);
        })
        ->when($request_page_type == 'denied-requests' , function($query){
            $query->whereIn('status' ,['denied']);
        })
        ->when($request_page_type == 'pending-requests' , function($query){
            $query->where('is_updated',0)->whereIn('status' ,['pending']);
        })
        ->max('id');
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());

        $advertisement= Advertisement::where('id',$advertisement)->with('store')->withoutGlobalScope('translate')->with('translations')->firstOrFail();
        return view("admin-views.advertisement.details",compact('advertisement','nextId','previousId','request_page_type','language','defaultLang'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request,Advertisement $advertisement)
    {
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $request_page_type=$request?->request_page_type ;
        $advertisement->withoutGlobalScope('translate');
        $advertisement->load('translations');
        $total_adds=Advertisement::whereNotNull('priority')
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->count()+1;
        return view("admin-views.advertisement.edit",compact('advertisement','total_adds','request_page_type','language','defaultLang'));
    }

    public function status(Request $request)
    {

        $request->validate([
            'pause_note' => ['required_if:status,paused', new WordValidation],
            'cancellation_note' => ['required_if:status,denied', new WordValidation],
        ]);


        $push_notification_status=null;
        $reataurant_push_notification_status=null;
        $reataurant_push_notification_title='';
        $reataurant_push_notification_description='';
        $advertisement =Advertisement::where('id',$request->id)->with('store')->first();
        $advertisement->status = in_array($request->status,['paused','approved','denied']) ? $request->status : $advertisement->status;
        $advertisement->pause_note = $request?->pause_note ?? null;
        $advertisement->cancellation_note = $request?->cancellation_note ?? null;
        $advertisement->is_updated =0;
        $advertisement?->save();
        if( $request->status == 'paused'){
            $reataurant_push_notification_title=translate('Advertisement_Paused');
            $reataurant_push_notification_description=translate('Admin_has_paused_your_advertisement');
            $email_type='advertisement_pause';
            Toastr::success( translate('messages.Advertisement_Paused_Successfully'));
            $push_notification_status=Helpers::getNotificationStatusData('store','store_advertisement_pause','push_notification_status' ,$advertisement?->store?->id);
            }
        elseif($request->status == 'approved' && $request?->approved == null){
            $reataurant_push_notification_title=translate('Advertisement_Resumed');
            $reataurant_push_notification_description=translate('Admin_has_resumed_your_advertisement');
            $email_type='advertisement_resume';
            Toastr::success(translate('messages.Advertisement_Resumed_Successfully'));
            $push_notification_status=Helpers::getNotificationStatusData('store','store_advertisement_resume','push_notification_status' ,$advertisement?->store?->id);
        }elseif($request->status == 'denied'){
            $email_type='advertisement_deny';
            $reataurant_push_notification_title=translate('Advertisement_Denied');
            $reataurant_push_notification_description=translate('Admin_has_denied_your_advertisement');
            $push_notification_status=Helpers::getNotificationStatusData('store','store_advertisement_deny','push_notification_status' ,$advertisement?->store?->id);
            Toastr::success(translate('messages.Advertisement_Denied_Successfully'));
            }
        else{
            $reataurant_push_notification_title=translate('Advertisement_Approved');
            $reataurant_push_notification_description=translate('Admin_has_approved_your_advertisement');
            $push_notification_status=Helpers::getNotificationStatusData('store','store_advertisement_approval','push_notification_status' ,$advertisement?->store?->id);
            $email_type='advertisement_approved';
            Toastr::success(translate('messages.Advertisement_approved_Successfully'));
        }

        try {
            if( $push_notification_status  && $advertisement?->store?->vendor?->firebase_token ){

                $data = [
                    'title' => $reataurant_push_notification_title,
                    'description' => $reataurant_push_notification_description,
                    'order_id' => '',
                    'image' => '',
                    'type' => 'advertisement',
                    'advertisement_id' => $advertisement->id,
                    'order_status' => '',
                ];
                Helpers::send_push_notif_to_device($advertisement->store->vendor->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $advertisement->store->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            if (config('mail.status') ) {


                if(Helpers::getNotificationStatusData('store','store_advertisement_approval','mail_status',$advertisement?->store?->id) &&  $email_type == 'advertisement_approved' &&  Helpers::get_mail_status('advertisement_approved_mail_status_store') == '1'){
                    Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,$email_type ,$advertisement->id));
                }


                if(Helpers::getNotificationStatusData('store','store_advertisement_pause','mail_status',$advertisement?->store?->id)  &&  $email_type == 'advertisement_pause' &&  Helpers::get_mail_status('advertisement_pause_mail_status_store') == '1'){
                    Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,$email_type ,$advertisement->id));
                }


                if(Helpers::getNotificationStatusData('store','store_advertisement_deny','mail_status',$advertisement?->store?->id)  &&  $email_type == 'advertisement_deny' &&  Helpers::get_mail_status('advertisement_deny_mail_status_store') == '1'){
                    Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,$email_type ,$advertisement->id));
                }


                if(Helpers::getNotificationStatusData('store','store_advertisement_resume','mail_status',$advertisement?->store?->id)  &&  $email_type == 'advertisement_resume' &&  Helpers::get_mail_status('advertisement_resume_mail_status_store') == '1'){
                    Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,$email_type ,$advertisement->id));
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }



        return back();
    }
    public function isComments(Request $request)
    {

        // $request->validate([
        //     'pause_note' => ['required_if:status,paused', new WordValidation],
        //     'cancellation_note' => ['required_if:status,denied', new WordValidation],
        // ]);

        $advertisement =Advertisement::where('id',$request->id)->with('store')->first();
       if( $advertisement->iscomments == 0){
        $advertisement->iscomments = 1;
       }else{
        $advertisement->iscomments = 0;

       }

       // $advertisement->is_updated =0;
        $advertisement?->save();
        if( $advertisement->iscomments == 0){
            $email_type='advertisement_pause';
            Toastr::success( translate('messages.Advertisement_Paused_Successfully'));
        }
        elseif($advertisement->iscomments == 1){
            $email_type='advertisement_resume';
            Toastr::success(translate('messages.Advertisement_Resumed_Successfully'));
        }


        return back();
    }
    public function paidStatus(Request $request)
    {
        $advertisement =Advertisement::where('id',$request->add_id)->first();
        $advertisement->is_paid =$advertisement->is_paid  == 1 ? 0 :1 ;
        $advertisement?->save();
        Toastr::success(translate('messages.Payment_status_updated_Successfully'));
        return back();
    }
    public function priority(Request $request)
    {

        $advertisement =Advertisement::where('id',$request->priority_id)->first();
        $oldPriority = $advertisement['priority'];
        $newPriority = $request['priority_value'] ?? null;
        if ($oldPriority != $newPriority) {

            if ($oldPriority === null) {
                Advertisement::where('priority', '>=', $newPriority)
                ->when(is_numeric(config('module')['current_module_id']), function($query){
                    $query->where('module_id', config('module')['current_module_id']);
                })
                    ->lockForUpdate() // Lock rows for update
                    ->increment('priority');

            } else if ($newPriority !== null) {
                if ($newPriority < $oldPriority) {
                    Advertisement::whereBetween('priority', [$newPriority, $oldPriority - 1])
                    ->when(is_numeric(config('module')['current_module_id']), function($query){
                        $query->where('module_id', config('module')['current_module_id']);
                    })
                        ->lockForUpdate()
                        ->increment('priority');

                } else if ($newPriority > $oldPriority) {
                    Advertisement::whereBetween('priority', [$oldPriority + 1, $newPriority])
                    ->when(is_numeric(config('module')['current_module_id']), function($query){
                        $query->where('module_id', config('module')['current_module_id']);
                    })
                        ->lockForUpdate()
                        ->decrement('priority');

                }
            }
        }

        $advertisement->priority = $newPriority;
        $advertisement?->save();

        $adds=Advertisement::whereNotNull('priority')
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })

        ->orderByRaw('ISNULL(priority), priority ASC')->get();

        $newPriority = 1;
        foreach ($adds as $advertisement) {
            $advertisement->priority = $newPriority++;
            $advertisement->save();
        }



        Toastr::success(  translate('messages.Advertisement_priority_updated_Successfully'));
        return back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdvertisementUpdateRequest $request, Advertisement $advertisement)
    {
        $dateRange = $request->dates;
        list($startDate, $endDate) = explode(' - ', $dateRange);
        $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($startDate));
        $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($endDate));
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();


        if( $advertisement->add_type != $request->advertisement_type){

            if($request->advertisement_type == 'video_promotion' &&  !$request->has('video_attachment')){
                return response([ 'file_required' => 1 , 'message' => translate('You_must_need_to_add_a_promotional_video_file')], 200);
            }

            if($request->advertisement_type == 'store_promotion' &&  (!$request->has('cover_image') || !$request->has('profile_image'))  ){
                return response([ 'file_required' => 1 , 'message' => translate('You_must_need_to_add_cover_&_profile_image')], 200);
            }

            if($advertisement->cover_image && $request->advertisement_type == 'video_promotion')
            {
                Helpers::check_and_delete('advertisement/' , $advertisement->cover_image);
            }
            if($advertisement->profile_image && $request->advertisement_type == 'video_promotion')
            {
                Helpers::check_and_delete('advertisement/' , $advertisement->profile_image);
            }


            if($advertisement->video_attachment && $request->advertisement_type == 'store_promotion')
            {
                Helpers::check_and_delete('advertisement/' , $advertisement->video_attachment);
            }
        }


        $oldPriority = $advertisement['priority'];
        $newPriority = $request['priority'] ?? null;
        if ($oldPriority != $newPriority) {

            if ($oldPriority === null) {
                Advertisement::where('priority', '>=', $newPriority)
                ->when(is_numeric(config('module')['current_module_id']), function($query){
                    $query->where('module_id', config('module')['current_module_id']);
                })
                    ->lockForUpdate() // Lock rows for update
                    ->increment('priority');

            } else if ($newPriority !== null) {
                if ($newPriority < $oldPriority) {
                    Advertisement::whereBetween('priority', [$newPriority, $oldPriority - 1])
                    ->when(is_numeric(config('module')['current_module_id']), function($query){
                        $query->where('module_id', config('module')['current_module_id']);
                    })
                        ->lockForUpdate()
                        ->increment('priority');

                } else if ($newPriority > $oldPriority) {
                    Advertisement::whereBetween('priority', [$oldPriority + 1, $newPriority])
                    ->when(is_numeric(config('module')['current_module_id']), function($query){
                        $query->where('module_id', config('module')['current_module_id']);
                    })
                        ->lockForUpdate()
                        ->decrement('priority');
                }
            }
        }
        $advertisement->store_id = $request->store_id;
        $advertisement->title = $request->title[array_search('default', $request->lang)];
        $advertisement->description = $request->description[array_search('default', $request->lang)];
        $advertisement->start_date = $startDate;
        $advertisement->end_date = $endDate;
        $advertisement->priority = $newPriority;
        $advertisement->is_rating_active = $request->advertisement_type == 'store_promotion' ?  $request?->rating ?? 0 : 0;
        $advertisement->is_review_active = $request->advertisement_type == 'store_promotion' ?  $request?->review ?? 0 : 0;

        $advertisement->is_updated =0;
        $advertisement->status = 'approved';


        $advertisement->add_type = $request->advertisement_type;
        $advertisement->cover_image = $request->has('cover_image') &&  $request->advertisement_type == 'store_promotion' ? Helpers::update(dir:'advertisement/', old_image: $advertisement->cover_image, format:$request->file('cover_image')->getClientOriginalExtension(), image: $request->file('cover_image')) : $advertisement->cover_image;
        $advertisement->profile_image = $request->has('profile_image') &&  $request->advertisement_type == 'store_promotion' ? Helpers::update(dir:'advertisement/', old_image: $advertisement->profile_image, format:$request->file('profile_image')->getClientOriginalExtension(), image: $request->file('profile_image')) : $advertisement->profile_image;
        $advertisement->video_attachment = $request->has('video_attachment') &&  $request->advertisement_type == 'video_promotion' ? Helpers::update(dir:'advertisement/', old_image: $advertisement->video_attachment, format:$request->file('video_attachment')->getClientOriginalExtension(), image: $request->file('video_attachment')) : $advertisement->video_attachment;


        $advertisement->save();
        Helpers::add_or_update_translations(request: $request, key_data:'title' , name_field:'title' , model_name: 'Advertisement' ,data_id: $advertisement->id,data_value: $advertisement->title);
        Helpers::add_or_update_translations(request: $request, key_data:'description' , name_field:'description' , model_name: 'Advertisement' ,data_id: $advertisement->id,data_value: $advertisement->description);

        try {
            if(  Helpers::getNotificationStatusData('store','store_advertisement_approval','push_notification_status' ,$advertisement?->store?->id)  && $advertisement?->store?->vendor?->firebase_token && $request?->request_page_type ){

                $data = [
                    'title' => translate('Advertisement_Approved'),
                    'description' => translate('Admin_has_approved_your_advertisement'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'advertisement',
                    'advertisement_id' => $advertisement->id,
                    'order_status' => '',
                ];
                Helpers::send_push_notif_to_device($advertisement->store->vendor->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $advertisement->store->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


                if( Helpers::getNotificationStatusData('store','store_advertisement_approval','mail_status' ,$advertisement?->store?->id)  && config('mail.status') && Helpers::get_mail_status('advertisement_approved_mail_status_store') == '1' && $request?->request_page_type){
                    Mail::to($advertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($advertisement?->store?->name,'advertisement_approved' ,$advertisement->id));
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return response()->json(['message' => translate('messages.Advertisement_Updated_Successfully')], 200);
    }


    public function copyAdd(Advertisement $advertisement)
    {
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $total_adds=Advertisement::whereNotNull('priority')
        ->when(is_numeric(config('module')['current_module_id']), function($query){
            $query->where('module_id', config('module')['current_module_id']);
        })
        ->count()+1;
        return view("admin-views.advertisement.edit",compact('advertisement','total_adds','language','defaultLang'));
    }
    public function updateDate(Advertisement $advertisement,Request $request)
    {

        $startDate = \Carbon\Carbon::createFromFormat('m/d/Y',trim($request->start_date) );
        $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($request->end_date));
        $startDate = $startDate->startOfDay();
        $endDate = $endDate->endOfDay();


        if ($endDate < $startDate) {
            Toastr::error(translate('messages.End date must be greater than start date'));
            return back();
        }
        $advertisement->start_date = $startDate;
        $advertisement->end_date = $endDate;

        $advertisement->save();
        Toastr::success(translate('Validity_updated'));
        return back();
    }

    public function copyAddPost(Advertisement $advertisement , AdvertisementUpdateRequest $request)
    {

            $dateRange = $request->dates;
            list($startDate, $endDate) = explode(' - ', $dateRange);
            $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($startDate));
            $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($endDate));
            $startDate = $startDate->startOfDay();
            $endDate = $endDate->endOfDay();


            $newPriority = $request['priority'];
            $request['priority'] > 0 ? Advertisement::where('priority', '>=', $newPriority)

            ->when(is_numeric(config('module')['current_module_id']), function($query){
                $query->where('module_id', config('module')['current_module_id']);
            })

            ->increment('priority') : null;

            $newAdvertisement = New Advertisement();
            $newAdvertisement->store_id = $request->store_id;
            $newAdvertisement->add_type = $request->advertisement_type;
            $newAdvertisement->title = $request->title[array_search('default', $request->lang)];
            $newAdvertisement->description = $request->description[array_search('default', $request->lang)];
                $newAdvertisement->start_date = $startDate;
            $newAdvertisement->end_date = $endDate;
            $newAdvertisement->priority = $newPriority;

            $newAdvertisement->is_rating_active = $request->advertisement_type == 'store_promotion' ?  $request?->rating ?? 0 : 0;
            $newAdvertisement->is_review_active = $request->advertisement_type == 'store_promotion' ?  $request?->review ?? 0 : 0;

            $newAdvertisement->is_paid =1;
            $newAdvertisement->created_by_id = auth('admin')->id();
            $newAdvertisement->created_by_type = 'App\Models\Admin';
            $newAdvertisement->status = 'approved';

        if($request->advertisement_type == 'store_promotion' ){
            if($request->has('cover_image')){
                $newAdvertisement->cover_image =  Helpers::upload(dir: 'advertisement/', format:$request->file('cover_image')->getClientOriginalExtension(), image:$request->file('cover_image'));
            } else{
                $newAdvertisement->cover_image =$this->copyAttachment($advertisement ,'cover_image');
            }
            if($request->has('profile_image')){
                $newAdvertisement->profile_image =  Helpers::upload(dir: 'advertisement/', format:$request->file('profile_image')->getClientOriginalExtension(), image:$request->file('profile_image'));
            } else{
                $newAdvertisement->profile_image =$this->copyAttachment($advertisement ,'profile_image');
            }

        }

        if($request->advertisement_type == 'video_promotion' ){
            if($request->has('video_attachment')){
                $newAdvertisement->video_attachment =  Helpers::upload(dir: 'advertisement/', format:$request->file('video_attachment')->getClientOriginalExtension(), image:$request->file('video_attachment'));
            } else{
                $newAdvertisement->video_attachment =$this->copyAttachment($advertisement ,'video_attachment');
            }
        }

        $newAdvertisement->save();

        $newAdvertisement->module_id= $newAdvertisement->store?->module?->id;
        $newAdvertisement->module_type= $newAdvertisement->store?->module?->module_type;
        $newAdvertisement->save();

            Helpers::add_or_update_translations(request: $request, key_data:'title' , name_field:'title' , model_name: 'Advertisement' ,data_id: $newAdvertisement->id,data_value: $newAdvertisement->title);
            Helpers::add_or_update_translations(request: $request, key_data:'description' , name_field:'description' , model_name: 'Advertisement' ,data_id: $newAdvertisement->id,data_value: $newAdvertisement->description);

            try {

                if( Helpers::getNotificationStatusData('store','store_advertisement_create_by_admin','push_notification_status',$newAdvertisement?->store?->id ) && $newAdvertisement?->store?->vendor?->firebase_token ){

                    $data = [
                        'title' => translate('New_Advertisement'),
                        'description' => translate('Admin_has_added_a_new_advertisement_for_your_store'),
                        'order_id' => '',
                        'image' => '',
                        'type' => 'advertisement',
                        'advertisement_id' => $newAdvertisement->id,
                        'order_status' => '',
                    ];
                    Helpers::send_push_notif_to_device($newAdvertisement->store->vendor->firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $newAdvertisement->store->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                if(Helpers::getNotificationStatusData('store','store_advertisement_create_by_admin','mail_status',$newAdvertisement?->store?->id ) && config('mail.status') && Helpers::get_mail_status('advertisement_create_mail_status_store') == '1'){
                    Mail::to($newAdvertisement?->store?->email)->send(new \App\Mail\AdversitementStatusMail($newAdvertisement?->store?->name,'advertisement_create' ,$newAdvertisement->id));
            }
            } catch (\Throwable $th) {
                //throw $th;
            }
            return response()->json(['message' => translate('messages.Advertisement_Added_Successfully')], 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $advertisement =Advertisement::where('id',$id)->first();

        if($advertisement?->cover_image)
        {
            Helpers::check_and_delete('advertisement/' , $advertisement->cover_image);
        }
        if($advertisement?->profile_image)
        {
            Helpers::check_and_delete('advertisement/' , $advertisement->profile_image);
        }
        if($advertisement?->video_attachment)
        {
            Helpers::check_and_delete('advertisement/' , $advertisement->video_attachment);
        }
        $advertisement?->translations()?->delete();
        $module_id =$advertisement?->module_id;
        $advertisement?->delete();

        $adds=Advertisement::whereNotNull('priority')->where('module_id',$module_id)
        ->orderByRaw('ISNULL(priority), priority ASC')->get();

        $newPriority = 1;
        foreach ($adds as $advertisement) {
            $advertisement->priority = $newPriority++;
            $advertisement->save();
        }

        Toastr::success(translate('messages.Advertisement_deleted_successfully'));
        return back();
    }
    private function copyAttachment($attachment , $fileKeyName)
    {

        $oldDisk = 'public';
            if ($attachment->storage && count($attachment->storage) > 0) {
                foreach ($attachment->storage as $value) {
                    if ($value['key'] == $fileKeyName) {
                        $oldDisk = $value['value'];
                        }
                }
            }
                    $oldPath = "advertisement/{$attachment->{$fileKeyName}}";
                    $newFileName =Carbon::now()->toDateString() . "-" . uniqid() . '.'.explode('.',$attachment->{$fileKeyName})[1];
                    $newPath = "advertisement/{$newFileName}";
                    $dir = 'advertisement/';
                    $newDisk = Helpers::getDisk();

            try{
                if (Storage::disk($oldDisk)->exists($oldPath)) {
                    if (!Storage::disk($newDisk)->exists($dir)) {
                        Storage::disk($newDisk)->makeDirectory($dir);
                    }
                    $fileContents = Storage::disk($oldDisk)->get($oldPath);
                    Storage::disk($newDisk)->put($newPath, $fileContents);
                }
            } catch (\Exception $e) {
            }

            return $newFileName ?? null;

    }






}
