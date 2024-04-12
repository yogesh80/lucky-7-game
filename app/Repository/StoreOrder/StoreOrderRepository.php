<?php

namespace App\Repository\StoreOrder;
use Illuminate\Http\Request;
use App\Models\{Order, Address, SubOrder,UserWallet,UserWalletTotal, OrderLog, SmsTemplate, Store, User, Coupon};
use Session;
use Auth;
use Hash;
use DB;
use App\Repository\Home\HomeInterface;
class StoreOrderRepository implements StoreOrderInterface
{
    public $homeRepo,$orderRepo,$orderlog,$address,$subOrder,$store,$user,$coupon;
    public $wallet_deduction;
    public $refund_deduction;

    public function __construct($refund_deduction=0,$wallet_deduction=0,HomeInterface $homeRepo,Order $orderRepo, OrderLog $orderlog,Address $address, SubOrder $subOrder, Store $store, User $user,Coupon $coupon)
    {
        $this->orderRepo = $orderRepo;
        $this->orderlog = $orderlog;
        $this->address = $address;
        $this->subOrder = $subOrder;
        $this->store = $store;
        $this->user = $user;
        $this->coupon = $coupon;
        $this->homeRepo = $homeRepo;

        $this->wallet_deduction = $wallet_deduction;
        $this->refund_deduction = $refund_deduction;

    }

    //user registration and update
     public function addUserDetails($user_data)
    {   //get current store
          $store = $this->store->domainData();
          DB::begintransaction();

         //check user is exist or not with phone or email
          $activeUser = $this->user->where([['user_type',4],['phone',$user_data['full_phone']]]);
                        if(isset($user_data['email'])){
                           $activeUser=$activeUser->orWhere('email',$user_data['email']);
                        }
          $activeUser=$activeUser->first(['id','first_name','email','phone','car_make','car_color','car_license']);

         if(isset($activeUser)){
                   //old user
                    $updatedUser = $this->user->where('id',$activeUser->id)->update([
                        'first_name' => $user_data['name'] ?? $activeUser->first_name,
                        'phone' => $user_data['full_phone'] ?? $activeUser->phone,
                        'car_make' => $user_data['make'] ?? $activeUser->car_make,
                        'car_color' => $user_data['color'] ?? $activeUser->car_color,
                        'car_license' => $user_data['licence'] ?? $activeUser->car_license,
                        'email' => isset($activeUser->email) ? $activeUser->email :  ($user_data['email'] ?? NULL)
                    ]);
                    Session::put('UserInfo',$activeUser);
                    $response['code'] = 200;
                    $response['data'] = Session::get('UserInfo');
                    DB::commit();
                    return array('status' => true, 'message' => 'User updated successfully.', 'data' => $response);
         }else{
                  //new user
                        $userObj = $this->user;
                        $this->buildUserObj($user_data,$userObj, 1);
                        $userObj->save();
                        $userObj->roles()->attach(4);
                        $response['code'] = 201;
                        Session::put('UserInfo',$userObj);
                        $response['data'] = Session::get('UserInfo');
                        DB::commit();
                        $this->CompleteRegistration($userObj,$store->country->currency);
                        return array('status' => true, 'message' => 'User created successfully.', 'data' => $response);
         }
    }

    public function placeOrder($additionalData,$domainData)
    {
        try {
               $standard = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
               $eastern_arabic_symbols = array("يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر");
               $arabic_date = str_replace($eastern_arabic_symbols , $standard ,date('Y-M-d', strtotime(Session::get('OrderDate'))));
                //user cart
                  $cart = Session::get('cart');
                //get user
                  $userData = Session::get('UserInfo');
                // wallet and refund deduction
                 $this->checkAdditionalDeduction($additionalData,$domainData,$cart,$userData);
                 $appliedCoupon=Session::get('coupon');
                 $couponDeduction=0;
                 $expressDeliveryFee = $additionalData['express_Delivery'] === "true" ? round(Session::get('OrderMode')['data']['fast_delivery']/Session::get('currency_info')['exchange_rate'],3) : 0 ;
                 $deliveryFee=(Session::get('OrderMode')['data']['orderMode']=='Car Pickup' || $additionalData['orderMode'] == 'Digital Product') ? 0 : (Session::get('orderDataAddress')['deliveryfee'] ?? $additionalData['deliveryCharge']);
                 if(isset($appliedCoupon) && $appliedCoupon->free_delivery=="1"){
                    $deliveryFee=0;
                    $couponDeduction=Session::get('discount.discount_price') ?? 0;
                 }
                 $orderTotal=((double)$cart->totalPrice +  (double)$deliveryFee) + $expressDeliveryFee -((double)$this->wallet_deduction + (double)$this->refund_deduction + (double)$couponDeduction);

                 //check for address
                 if($additionalData['orderMode'] =="Delivery" && Session::has('orderDataAddress')){
                      $address = $this->createUserAddress($userData,$domainData);

                 }

                DB::begintransaction();
             //create order
             $data=[
                        'user_id'=>$userData->id,
                        'store_id'=>$domainData->id,
                        'address_id'=>isset($address) ? $address->id : NULL,
                        'coupon_id'=>isset($appliedCoupon) ? $appliedCoupon->id : NULL,
                        'store_branch_id'=>Session::get('OrderMode')['data']['branchID'] ??  $domainData->storeBranches->first()->id,
                        'wallet_redem'=>$additionalData['wallet_redem'] ?? '0',
                        'code'=>ucfirst(substr(md5(time()), 0,6)),
                        'order_entry_date'=>date('Y-m-d H:i:s'),
                        'order_status'=>($additionalData['paymentMode'] == '1' || $additionalData['paymentMode'] == '2' || $additionalData['paymentMode'] == '5') ? '9' : '1',
                        'is_order_pickedup'=>($additionalData['orderMode'] == 'Car Pickup' || $additionalData['orderMode'] == 'Digital Product')  ? '1' : '0',
                        'currency'=>session()->get('currency_info')['currency'] ?? 'KWD',
                        'payment_method'=>$additionalData['paymentMode'],
                        'delivery_date'=>$arabic_date,
                        'delivery_from_time'=>Session::get('OrderTime'),
                        'delivery_to_time'=>Session::get('OrderTime'),
                        'delivery_cost'=>$deliveryFee,
                        'express_delivery'=>$expressDeliveryFee,
                        'order_type'=>$additionalData['orderMode'],
                        'total_no_of_items'=>count($cart->items),
                        'payment_id'=>NULL,
                        'wallet_amount_used'=>$this->wallet_deduction,
                        'refund_amount_used'=>$this->refund_deduction,
                        'notify'=>'0',
                        'is_send_to_delivery'=>'0',
                        'total_amount'=>$orderTotal,
                ];

               $orderId = DB::table('orders')->insertGetId($data);
               $subOrderData=$this->createSubOrderData($orderId,$domainData,$cart);
               $subOrderStatus=DB::table('sub_orders')->insert($subOrderData);

                DB::commit();
                    Session::forget('discount');
                    Session::forget('coupon');
                    $order=Order::where('id',$orderId)->first();

                     $orderLogObj = $this->orderlog->create(['order_id'=>$order->id,
                           'accepted_at'=>$additionalData['paymentMode'] == '1' || $additionalData['paymentMode'] == '2' ? date('Y-m-d H:i:s') : Null
                      ]);

                    $response = [];
                    $response['order'] = $order;
                    $response['userData'] = $userData;
                    return array('status' => true, 'data' => $response);

        } catch (Exception $e) {

             return array('status' => false, 'data' => $e->getMessage);
        }
    }

   public function createSubOrderData($orderId,$domainData,$cart)
   {
       $allData=[];
       foreach($cart->items as $key => $product) {
                $add_option=[];
                    if(isset($product['product_extra_option'])){
                        foreach($product['product_extra_option'] as $key => $option) {
                                $add_option[]=$option;
                        }
                    }
                  $allData[]=array(
                    'order_id'=>$orderId,
                    'product_id'=>$product['item']->id,
                    'product_name'=>$product['item']->name_en,
                    'product_price'=>$product['price'],
                    'product_qty'=>$product['qty'],
                    'instruction'=>$product['instruction'] ?? NULL,
                    'product_options'=>json_encode($add_option),
                  );
             }
        return $allData;
   }

    private function checkAdditionalDeduction($additionalData,$domainData,$cart,$userData)
    {

       $userTotalBalance=UserWalletTotal::where('store_id',$domainData->id)->where('user_id',$userData->id)->first();
       if($additionalData['wallet_redem'] == 1 && Auth::check() && isset($userTotalBalance)){
            $wallet_deduction=$additionalData['wallet_deduction'] ?? 0;
            $maxRedemptionValue = redempationRule($domainData,($cart->totalPrice));
            $user_wallet_deduction_applied=round($wallet_deduction/Session::get('currency_info')['exchange_rate'],3);

            //check that wallet balance is valid or not
            if($user_wallet_deduction_applied <= $userTotalBalance->total_cashback || $maxRedemptionValue == $wallet_deduction){
                $this->wallet_deduction = $wallet_deduction;
            }
       }

       if(isset($userTotalBalance) && $additionalData['refund_deduction'] != null && (double)$additionalData['refund_deduction'] > 0 && Auth::check() && $additionalData['refund_deduction'] <= $userTotalBalance->total_refund ){

               $this->refund_deduction=$additionalData['refund_deduction'] ?? 0;
       }
    }


    public function createUserAddress($userData,$domainData){
        if(Session::has('orderDataAddress')) {
                    $fullAddress = $this->buildFullAddress(Session::get('orderDataAddress'));
                    $addressArr = [];
                    $addressArr = $this->buildAddressArr(Session::get('orderDataAddress'), $userData, $fullAddress, $domainData, $addressArr);
                    $currentAddress=$this->address->updateOrCreate(
                          ['user_id' =>  $userData->id,'address'=>$addressArr['address']],
                           $addressArr
                     );
                    return $currentAddress;
        }
    }

     public function checkStatus($data)
     {
          $orderData = $this->orderRepo->where('code',$data['statusCode' ?? NULL])->with('OrderLog')->first();
            if(isset($orderData)) {
                return array('status' => true, 'data' => $orderData);
            } else {
                return array('status' => false, 'message' => 'No order found');
            }
       }

     public function buildFullAddress($addressDetail)
     {
              $cityData=city_name(Session::get('OrderMode.data.area_id'));
            if($addressDetail['type']=='house') {
                $fulladdress=trans('restaurant_lang.block').':'.$addressDetail['block'].','.trans('restaurant_lang.street').':'.$addressDetail['street'].','.trans('restaurant_lang.house_no').':'.$addressDetail['house_no'];
                if($addressDetail['avenue'] !=null) {
                    $fulladdress =   $fulladdress .' ,'.trans('restaurant_lang.avenue').':'.$addressDetail['avenue'];
                }
                if($addressDetail['special_direction'] !=null){
                    $fulladdress = $fulladdress.','.trans('restaurant_lang.special_directions').':'.$addressDetail['special_direction'];
                }
            } else if($addressDetail['type']=='appartment') {
                $fulladdress=trans('restaurant_lang.block').':'.$addressDetail['block'].','.trans('restaurant_lang.street').':'.$addressDetail['street'].','.trans('restaurant_lang.apartment_no').' : '.$addressDetail['house_no'].','.trans('restaurant_lang.building').' : '.$addressDetail['building'].','.trans('restaurant_lang.building').' : '.$addressDetail['floor'];
                if($addressDetail['avenue'] !=null) {
                    $fulladdress =   $fulladdress .' ,'.trans('restaurant_lang.avenue').':'.$addressDetail['avenue'];
                }
                if($addressDetail['special_direction'] !=null) {
                    $fulladdress = $fulladdress.','.trans('restaurant_lang.special_directions').':'.$addressDetail['special_direction'];
                }
            } else {
                $fulladdress=trans('restaurant_lang.block').':'.$addressDetail['block'].','.trans('restaurant_lang.street').':'.$addressDetail['street'].','.trans('restaurant_lang.office_no').': '.$addressDetail['house_no'].','.trans('restaurant_lang.building').' : '.$addressDetail['building'].','.trans('restaurant_lang.building').' : '.$addressDetail['floor'];
                if($addressDetail['avenue'] !=null) {
                    $fulladdress =   $fulladdress .' ,'.trans('restaurant_lang.avenue').':'.$addressDetail['avenue'];
                }
                if($addressDetail['special_direction'] !=null) {
                    $fulladdress = $fulladdress.','.trans('restaurant_lang.special_directions').':'.$addressDetail['special_direction'];
                }
             }
             return $fulladdress;
        }


      public function buildAddressArr($addressDetail, $userData, $fullAddress, $domainData, $addressArr)
       {
        $cityData=city_name(Session::get('OrderMode.data.area_id'));
        $countryData=country_name($cityData['country_id']);
        $addressArr['user_id'] = $userData->id;
        $addressArr['state_id'] =Session::get('OrderMode.data.area_id') ?? NULL;
        $addressArr['area_ar'] = $cityData['name_ar'] ?? '';
        $addressArr['area'] = $cityData['name'] ?? '';
        $addressArr['add_type'] = $addressDetail['type'] ?? '';
        $addressArr['special_direction'] = $addressDetail['special_direction'] ?? '';
        $addressArr['street'] = $addressDetail['street'] ?? '';
        $addressArr['house_no'] = $addressDetail['house_no'] ?? '';
        $addressArr['address'] = isset($fullAddress) ? $fullAddress :  '';
        $addressArr['buliding'] = $addressDetail['building'] ?? '';
        $addressArr['floor'] = $addressDetail['floor'] ?? '';
        $addressArr['country'] = $countryData['name'] ?? '';
        $addressArr['block'] = $addressDetail['block'] ?? '';
        $addressArr['country_ar'] = $countryData['name_ar'] ?? $countryData['name'];
        $addressArr['latitude'] = $addressDetail['lat'] ?? $cityData['latitude'];
        $addressArr['longitude'] = $addressDetail['long'] ?? $cityData['longitude'];

        return $addressArr;

     }

     public function buildUserObj($data, $userObj,$pass)
    {

        $userObj->first_name = $data['name'];
        $userObj->email = $data['email'];
        $userObj->phone = $data['full_phone'];
        $userObj->country_id = $data['country_code'] ?? '+965';
        $userObj->car_make = $data['make'] ?? '';
        $userObj->car_color = $data['color'] ?? '';
        $userObj->car_license = $data['licence'] ?? '';
        if($pass === 1) {
            $userObj->password = Hash::make($data['full_phone']);
        }
    }
    public function CompleteRegistration($user_data,$currency)
    {
           $userData=$this->userDataFuncation($user_data);
           $customData['currency']=$currency;
           $this->homeRepo->webEventFB('CompleteRegistration',$userData,json_encode($customData));

    }

    public function userDataFuncation($user_data)
    {
        $remove_code_phone = preg_replace(
            '/\+(?:998|996|995|994|993|992|977|976|975|974|973|972|971|970|968|967|966|965|964|963|962|961|960|886|880|856|855|853|852|850|692|691|690|689|688|687|686|685|683|682|681|680|679|678|677|676|675|674|673|672|670|599|598|597|595|593|592|591|590|509|508|507|506|505|504|503|502|501|500|423|421|420|389|387|386|385|383|382|381|380|379|378|377|376|375|374|373|372|371|370|359|358|357|356|355|354|353|352|351|350|299|298|297|291|290|269|268|267|266|265|264|263|262|261|260|258|257|256|255|254|253|252|251|250|249|248|246|245|244|243|242|241|240|239|238|237|236|235|234|233|232|231|230|229|228|227|226|225|224|223|222|221|220|218|216|213|212|211|98|95|94|93|92|91|90|86|84|82|81|66|65|64|63|62|61|60|58|57|56|55|54|53|52|51|49|48|47|46|45|44\D?1624|44\D?1534|44\D?1481|44|43|41|40|39|36|34|33|32|31|30|27|20|7|1\D?939|1\D?876|1\D?869|1\D?868|1\D?849|1\D?829|1\D?809|1\D?787|1\D?784|1\D?767|1\D?758|1\D?721|1\D?684|1\D?671|1\D?670|1\D?664|1\D?649|1\D?473|1\D?441|1\D?345|1\D?340|1\D?284|1\D?268|1\D?264|1\D?246|1\D?242|1)\D?/'
           , ''
           , $user_data['phone']
           );
        $phone[]=Hash('SHA256',$remove_code_phone);
        $userData['client_user_agent']= $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0";
        $userData['client_ip_address']= $_SERVER['REMOTE_ADDR'] ?? "192.168.43.177";
        $userData['fn']=Hash('SHA256',$user_data['first_name']);
        $userData['ph']=json_encode(array_values($phone));
        return json_encode($userData);
    }



}
