<?php

namespace App\Repository\Home;

use App\Models\{User, Store, Category, Product,Integration, StoreBranch, Location, Order,};
use MyFatoorahPaymentGateway;
use Session;
use Auth;
use DB;
use Carbon\Carbon;
use App\Jobs\FacebookConversionApiEvent;
use App\Services\Facebookconversion;
use Toastr;
use Hesabe\Payment\HesabeCrypt;
class HomeRepository implements HomeInterface
{
    /**
     * @var Store
     */
    private $storeRepo;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Category
     */
    private $category;
    /**
     * @var Product
     */
    private $product;
    /**
     * @var StoreBranch
     */
    private $branch;
    /**
     * @var Location
     */
    private $location;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var StoreRewardOffer
     */

    /**
     * HomeRepository constructor.
     *
     * @param Store $storeRepo
     * @param Category $category
     * @param Product $product
     * @param StoreBranch $branch
     * @param Location $location
     * @param Order $order

     * @param User $user
     */
    public function __construct(Store $storeRepo, Category $category, Product $product, StoreBranch $branch, Location $location, Order $order,User $user)
    {
        $this->storeRepo = $storeRepo;
        $this->category = $category;
        $this->product = $product;
        $this->branch = $branch;
        $this->location = $location;
        $this->order = $order;
        $this->user = $user;

    }


    public function showReadOnly()
    {
        try {
            $domainData = $this->storeRepo->domainData();

            if(isset($domainData->id)) {
                $data = $this->category->with(['items.stock','items.productOptions','items' => function($query){
                                    $query->orderBy('position', 'ASC');
                                    $query->where('is_active',1);
                                    $query->where('deleted_at',null);
                             }])->whereHas('branches', function ($q){
                                    $q->where('is_active',1);
                             })
                            ->where('is_active',1)
                            ->where('deleted_at', NULL)
                            ->where('store_id',$domainData->id)
                            ->orderBy('sort','ASC')
                            ->orderBy('name', 'ASC')
                            ->get();

                return array('status' => true, 'data' => $data,'domainData'=>$domainData);
            } else {
                return array('status' => false, 'data' => 'Something went wrong.');
            }

        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function showReadOnlyDetails($id)
    {
        try {
            $domainData = $this->storeRepo->domainData();

            if(isset($domainData->id)) {
                $data = $this->product->where('id', $id)->where('is_active',1)->with('stock')->where('deleted_at', NULL)->first();
                return array('status' => true, 'data' => $data,'domainData'=>$domainData);
            } else {
                return array('status' => false, 'data' => 'Something went wrong.');
            }

        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function search($data)
    {
        try {
            if ($data['current_key'] == '') {
                return array('status' => false, 'data' => 'Please enter a valid product name.');
            }
            $domainData = $this->storeRepo->domainData();
            $products = $this->product->where('store_id', $domainData->id)
                                ->where('is_active', 1)
                                ->where('name_en', 'LIKE', '%' . $data['current_key'] . "%")
                                ->orWhere([
                                        ['name_ar', 'LIKE', '%' . $data['current_key'] . "%"],
                                        ['store_id', $domainData->id],
                                        ['is_active', '=', 1,],['deleted_at',Null]
                                    ])
                                ->get();
            if(count($products) > 0) {
                return array('status' => true, 'data' => $products,'domainData'=>$domainData);
            }
            return array('status' => false, 'message' => 'No Product Found.','domainData'=>$domainData);

        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function show($slug)
    {
        try {
              $domainData = $this->storeRepo->domainData();
            $data = $this->product->where('store_id',$domainData->id)->where('deleted_at', NULL)->where('slug', $slug)->with('productOptions','productOptions.productExtraOptions','productOptions.productExtraOptions.option')->first();


            if($data) {
                 /*****************facebook */
             if(isset($domainData->integration['facebook_conversion_pixel_id']) && $domainData->integration['facebook_conversion_token']){
                  $userData['client_user_agent']= $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0";
                  $userData['client_ip_address']= $_SERVER['REMOTE_ADDR'] ?? "192.168.43.177";

                  $customData['value']=$data->price;
                  $customData['currency']= session()->get('currency_info')['currency'] ?? 'KWD';
                  $customData['content_category']=$data['productCategories'][0]['name_ar'] ?? $data['name_ar'];
                  $customData['content_name']=$data['name_ar'];
                  $customData['content_ids']=["$data->id"];
                  $customData['content_type']="product";

                $this->webEventFB('ViewContent',json_encode($userData),json_encode($customData));
              }

                return array('status' => true, 'data' => $data,'domainData'=>$domainData);
            }

            return array('status' => false, 'data' => 'Product not found.');
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function branch()
    {
        try {
            $domainData = $this->storeRepo->domainData();
            $allBranches = $domainData->load('storeBranches')['storeBranches'];

            return array('status' => true, 'data' => $allBranches,'domainData'=>$domainData);
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function showBranch($id)
    {
        try {
            $storeBranch = $this->branch->where('id', $id)->first();
            return array('status' => true, 'data' => $storeBranch);
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function orderMode()
    {
        try {
            $domainData = $this->storeRepo->domainData();
            $data = $this->location->where('store_id', $domainData->id)->get();
            $stateData = $data->groupBy('state_id');

            return array('status' => true, 'data' => $stateData,'domainData'=>$domainData);
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function orderSuccess($data, $code)
    {
        try {
            $response = [];
            Session::forget('cart');

           $orderData = $this->order->where('code', $code)->with('subOrders','User')->first();

              if(session::get('payment_type')=="hasabe" && isset($data['data'])){
                   $decryptedResponse = HesabeCrypt::decrypt($data['data'],'PkW64zMe5NVdrlPVNnjo2Jy9nOb7v1Xg','5NVdrlPVNnjo2Jy9');
                   $paymentData=json_decode($decryptedResponse,true)["response"];

                   DB::table('my_fatoorahs')->insert([
                        'payment_id'=>$paymentData['paymentId'] ?? NULL,
                        'payment_method_id'=>$paymentData['method'] ?? NULL,
                        'invoice_value'=>$paymentData['amount'] ?? NULL,
                        'invoice_id'=>$paymentData['transactionId'] ?? NULL,
                        'json'=>json_encode($paymentData),
                        'currency'=>$orderData['currency'] ?? "KWD",
                        'invoice_status'=>$paymentData['resultCode'],
                        'track_id'=>$paymentData['transactionId'],
                        'customer_name'=>$orderData['User']['first_name'] ?? NULL,
                        'customer_email'=>$orderData['User']['email'] ?? NULL,
                        'payment_url'=>$paymentData['transactionId'],
                        'created_at'=>$paymentData['paidOn']

                   ]);

                   DB::table('orders')->where('code', $code)->update(['order_status' => '2', 'payment_id' =>$paymentData['paymentId']]);

              }

            if(isset($data['paymentId']))
            {
                $orderData->update(['order_status' => '2', 'payment_id' => $data['paymentId']]);
            }
            $response['code'] = $code;
            $response['orderData'] = $orderData;
            if($response['orderData']){
                 Session::forget('payment_type');
                 return array('status' => true, 'data' => $response);
            }

            return array('status' => false, 'data' => $response);
        } catch (Exception $e) {

            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function orderFailed($data,$code)
    {
        try {

            $orderData = $this->order->where('code', $code)->first();

            if(isset($data['paymentId']))
            {
                $order_status = MyFatoorahPaymentGateway::isPaymentExecuted($data['paymentId']);
                if($order_status->status == false) {
                    $orderData->update(['order_status' => '9', 'payment_id' => $data['paymentId']]);
                }
            }

            return array('status' => true, 'message' => 'Order has been failed.');
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function secondProductDetail($id)
    {
        try {
            $domainData = $this->storeRepo->domainData();
            $data=$this->category->where('id',$id)->with(['items.stock:id,product_id,stock_value','items.productOptions:id,product_id','items.productOptions.productExtraOptions:id,product_option_id,extra_option_price','items' => function($query){
               $query->orderBy('position', 'ASC');
               $query->where('is_active',1);
               $query->where('deleted_at',null);
             }])->where('is_active',1)
                ->where('store_id',$domainData->id)
                ->orderBy('sort','ASC')
                ->where('deleted_at', NULL)
                ->orderBy('name', 'ASC')
                ->first();

            if($data){
            return array('status' => true, 'data' => $data,'domainData'=>$domainData);
            }
            return false;

        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function yourorders()
    {
        try {
            $domainData = $this->storeRepo->domainData();
            $orderData = $this->order->where('user_id', Auth::user()->id)->where('store_id',$domainData->id)->get();
            return array('status' => true, 'data' => $orderData,'domainData'=>$domainData);
        } catch (Exception $e) {
            return array('status' => false, 'data' => $e->getMessage);
        }
    }

    public function buildUserWalletArr($orderData, $min_amount) {
        $userWalletArr['user_id'] = $orderData->user_id;
        $userWalletArr['store_id'] = $orderData->store_id;
        $userWalletArr['order_id'] = $orderData->id;
        $userWalletArr['type'] = 'Cashback';
        $userWalletArr['amount'] = $min_amount;

        return $userWalletArr;
    }


      public function pagViewEventForConversion($domainData)
    {
                 if(isset($domainData->integration) && $domainData->integration['facebook_conversion_token']){
                      $userData['client_user_agent']= $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (Macintosh; Intel Mac OS X x.y; rv:42.0) Gecko/20100101 Firefox/42.0";
                      $userData['client_ip_address']=$_SERVER['REMOTE_ADDR'];
                      $this->webEventFB('PageView',json_encode($userData));
                  }
                   //snapchat conversion api
                if (isset($domainData) && isset($domainData->integration['snapchat_pixel_id']) && $domainData->integration['snapchat_conversion_token']) {
                    $eventData['pixelId'] = $domainData->integration['snapchat_pixel_id'];
                    $eventData['accessToken'] = $domainData->integration['snapchat_conversion_token'];
                    $eventData['eventName'] = "PAGE_VIEW";
                    $eventData['ip_address'] = getVisIpAddr();
                    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $eventData['page_url'] = $actual_link;
                    $eventData['currency'] = session()->get('currency_info')['currency'];
                    $eventData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (Macintosh; Intel Mac OS X x.y; rv:42.0) Gecko/20100101 Firefox/42.0";
                    ProcessSnapchatBackgroundEvent::dispatch($eventData)->delay(now()->addMinutes(1));
                }
                return true;
    }

    public function webEventFB($eventName,$userData='null',$customData='null')
    {

        $domainData = $this->storeRepo->domainData();
        if(isset($domainData->integration['facebook_conversion_pixel_id']) && $domainData->integration['facebook_conversion_token']){

        $url=(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $t=time();
        $data='{
            "data": [
                {
                    "event_name": "'.$eventName.'",
                    "event_time": '.$t.',
                    "action_source": "website",
                    "event_source_url":"'.$url.'",
                    "user_data": '.$userData.',
                    "custom_data": '.$customData.',
                }
              ],


          }';

    $emailJob = (new FacebookConversionApiEvent($data,$domainData->integration));
    dispatch($emailJob);

        }else{
            return false;
        }
    }
}
