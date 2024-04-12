<?php

namespace App\Repository\Store;

use App\Models\{User, Store, StoreBranch, StorePlan, SubdomainStatus, MailTemplate, UserVerify,Currency};
use App\Repository\Store\StoreInterface;
use Mail;
use App\Notifications\StoreRegisterNotification;
use Auth;
use DB;
use Str;
use Illuminate\Support\Facades\Hash;
class StoreRepository implements StoreInterface
{
    /**
     * @var User
     */
    private $user;
    /**
     * @var Store
     */
    private $store;
    /**
     * @var StoreBranch
     */
    private $branch;
      /**
     * @var StorePlan
     */
    private $plan;

    /**
     * BaseRepository constructor.
     *
     * @param User $user
     * @param Store $store
     * @param StoreBranch $branch
     * @param StorePlan $plan
     */
    public function __construct(User $user, Store $store, StoreBranch $branch, StorePlan $plan)
    {
        $this->user = $user;
        $this->store = $store;
        $this->branch = $branch;
        $this->plan = $plan;
    }

    public function getAllStores()
    {
        $data = $this->store->with('user','currentStorePlan','storeBranches')->latest()->get();
        return $data;
    }

    public function getStoreById($id)
    {
        $data = $this->store->with('user','currentStorePlan','storeBranches')->where('id',$id)->first();
        return $data;
    }

    public function createStore($collection=[])
    {
        try {
            $arrUser = [];
            $arrPlan = [];
            $arrStore = [];
            $arrBranch = [];

            
           
            DB::begintransaction();

            $arrUser = $this->buildUserArr($collection,$arrUser);
            $user=$this->user::create($arrUser);
            $user->roles()->attach(2);

            $arrStore =  $this->buildStoreArr($collection, $arrStore, $user);
            $store=$this->store::create($arrStore);

          
           
            $arrBranch = $this->buildBranchArr($collection,$arrBranch,$store);
    
            $branch=$this->branch::create($arrBranch);

            $user->store_id=$store->id;
            $user->save();
           
            Currency::updateOrCreate([
                    'store_id'=> $store->id,
                    'country_id'=>$collection['currency_id']
                ],
                [
                    'is_base'=> '1',
                    'exchange_rate' => 1,    
                ]);
            
            $arrPlan = $this->buildPlanArr($collection, $arrPlan, $store);
            $plan=$this->plan::create($arrPlan);

            SubdomainStatus::create([
                'subdomain' => isset($store->subdomain) && trim($store->subdomain) != ''?trim($store->subdomain):'',
            ]);
            $this->NotifyAdmin($store,$user);

            $email_template = MailTemplate::where('id', 3)->first();

            $string_to_replace = array("{FULL_NAME}", "{EMAIL}", "{PASSWORD}");
            $string_replaced_by = array($user->full_name, $user->email, $collection['password']);
            $content = str_replace($string_to_replace, $string_replaced_by, $email_template->mail_body_en);

            $data['email'] = $user->email;
            $data['subject'] = $email_template->subject;
            $data['content'] = $content;
            
            mailSend($data);

            $verify_token = Str::random(64);
            $verify_email_template = MailTemplate::where('id', 2)->first();

            UserVerify::create([
                'user_id' => $user->id, 
                'token' => $verify_token
            ]);

            $string_to_replace = array("{FULL_NAME}");
            $content = str_replace($string_to_replace, $user->full_name, $verify_email_template->mail_body_en);

            $data['email'] = $user->email;
            $data['subject'] = $verify_email_template->subject;
            $data['content'] = $content;
            $data['verification_link'] = $verify_token;

            mailSend($data);

            DB::commit();
           
            return array('status' => true, 'data' => $store, 'message' => 'store created ');
        } catch (\Throwable $th) {
            dd($th);
            DB::rollback();
            return array('status' => false, 'data' => '', 'message' =>$th->getMessage());
        }
    }

    public function buildUserArr($collection,$arrUser)
    {

        $arrUser['first_name'] = $collection['name'];
        $arrUser['last_name'] = '';
        $arrUser['email'] = $collection['email'];  
        $arrUser['phone'] = $collection['phone'];
        $arrUser['email_verified_at'] =date("Y-m-d h:i:s");
        $arrUser['password'] = Hash::make($collection['password']);
        $arrUser['user_type'] ='2';
        return $arrUser;
    }

    public function buildPlanArr($collection, $arrPlan, $store)
    {
        $startDate = date("Y-m-d h:m:s");

        if($collection['plan_type'] == "Monthly"){
            $endDate = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 30 day"));
        } else {
            $endDate = date('Y-m-d',strtotime(date("Y-m-d", time()) . " + 365 day"));
        }

        
        $arrPlan['store_id'] = $store['id'];
        $arrPlan['subscripation_plans_id'] = $collection['plan'];
        $arrPlan['start_date'] = $startDate;  
        $arrPlan['end_date'] = $endDate;
        $arrPlan['plan_price'] = $collection['totalprice'];
        $arrPlan['payment_status'] = $collection['payment_status'];
        $arrPlan['plan_type'] = $collection['plan_type'];
        $arrPlan['plan_current_status'] = '1';

        return $arrPlan;

    }

    public function buildStoreArr($collection, $arrStore, $user)
    {
        $arrStore['user_id'] = $user['id'];
        $arrStore['store_payment_code'] = $collection['store_payment_code'] ?? null;
        $arrStore['store_name_en'] =$collection['store_name_en'];
        $arrStore['store_name_ar'] = $collection['store_name_ar'];  
        $arrStore['subdomain'] =str_replace(' ', '', $collection['subdomain']); 
        $arrStore['country_id'] = $collection['country_id'];  


        return $arrStore;

    }

    public function buildBranchArr($collection, $arrBranch, $store)
    {
        $arrBranch['store_id'] =  $store['id'];
        $arrBranch['branch_name_en'] = $collection['store_name_en'];
        $arrBranch['branch_name_ar'] = $collection['store_name_ar'];  
        $arrBranch['address_en'] = $collection['address'];
        $arrBranch['address_ar'] = $collection['address'];
        $arrBranch['customer_service_number'] = $collection['phone'];
        $arrBranch['lat'] = $collection['address_latitude'];
        $arrBranch['long'] = $collection['address_longitude'];

        return $arrBranch;
    }


    public function NotifyAdmin($store,$user)
    {
      
         $body = [
            'body_text' => 'New store registration has been done.',
            'body_text_arabic' =>'تم تسجيل متجر جديد', 
            'store_name_en' => $store->store_name_en,
            'store_name_ar' => $store->store_name_ar,
            'store_logo' => $store->store_logo_image,
            'link' =>  '/stores',
         ];
            $owner = User::find('1');
            $owner->notify(new StoreRegisterNotification($user,$body));
        }
    
}