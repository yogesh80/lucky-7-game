<?php

namespace App\Repository\Order;

use App\Notifications\UserActivityNotification;
use App\Models\{Order, MailTemplate, Address, UserWalletTotal, Currency, Location, Product, SubOrder, OrderLog, ProductStock, SmsTemplate, StoreRewardOffer, User, UserWallet, ProductExtraOption};
use Auth;
use DB;
use App\Jobs\{ProcessSnapchatBackgroundEvent};
use App\Jobs\OrderStatusChange;

class OrderRepository implements OrderInterface
{
    /**
     * @var Order
     */
    private $orderRepo;
    /**
     * @var Orderlog
     */
    private $orderlog;
    /**
     * @var SmsTemplate
     */
    private $smsTemplate;
    /**
     * @var StoreRewardOffer
     */
    private $StoreRewardOffer;
    /**
     * @var Product
     */
    private $dish;
    /**
     * @var SubOrder
     */
    private $subOrder;
    /**
     * @var User
     */
    private $user;
    /**
     * @var UserWallet
     */
    private $userWallet;

    /**
     * OrderRepository constructor.
     *
     * @param Order $orderRepo
     * @param OrderLog $orderlog
     * @param SmsTemplate $smsTemplate
     * @param StoreRewardOffer $StoreRewardOffer
     * @param Product $dish
     * @param SubOrder $subOrder
     * @param User $user
     * @param UserWallet $userWallet
     */
    public function __construct(Order $orderRepo, OrderLog $orderlog, SmsTemplate $smsTemplate, StoreRewardOffer $StoreRewardOffer, Product $dish, SubOrder $subOrder, User $user, UserWallet $userWallet)
    {
        $this->orderRepo = $orderRepo;
        $this->orderlog = $orderlog;
        $this->smsTemplate = $smsTemplate;
        $this->StoreRewardOffer = $StoreRewardOffer;
        $this->dish = $dish;
        $this->subOrder = $subOrder;
        $this->user = $user;
        $this->userWallet = $userWallet;
    }

    public function getAll()
    {
        try {
            $orders = $this->orderRepo
                ->where('order_status', '9')
                ->get();

            return array('status' => true, 'data' => $orders);
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }

    public function show($data)
    {
        try {
            $order = $this->orderRepo
                ->where('id', $data['id'])
                ->with('subOrders', 'User')
                ->first();

            $createForm = $this->createOrderPickedUpForm($order);

            $last = $createForm['data'] . $createForm['newData'] . $createForm['newData2'];

            $result['html'] = $last;
            $result['step'] = $order['order_status'];
            $result['code'] = $order['code'];

            return array('status' => true, 'data' => $result);
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }







    /******Change order Status */
    public function changeStatus($data)
    {

        $data = explode('_', $data['id']); //get id and current status of order
        $orderID = $data[1];
        $orderCurrentStatus = $data[0];
        //update status and  get requested order

        $this->orderRepo->where('id', $orderID)->update(['order_status' => $orderCurrentStatus]);

        $fetchRequestedOrder = $this->orderRepo->with('subOrders')->where('id', $orderID)->first();

        if (isset($fetchRequestedOrder)) {
            $this->NotifyMessage($fetchRequestedOrder, $orderCurrentStatus);
            return array('status' => true, 'data' => 'true');
        } else {
            return array('status' => false, 'data' => false);
        }
    }




    public function NotifyMessage($order, $status)
    {
        switch ($status) {
            case (0):
                //order canceled
                $message = 'has been cancelled by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['cancel_at' => date('Y-m-d H:i:s')]
                );

                if ($this->orderlog->where('order_id', $order->id)->where('accepted_at', '!=', Null)->first()) {
                    //update stock
                    foreach ($order['subOrders'] as $OrderDetail) {
                        $stock = ProductStock::where('product_id', $OrderDetail->product_id)->first();
                        $stock->stock_value += $OrderDetail->product_qty;
                        $stock->save();
                    }
                }
                //start-cashback
                if ($order['wallet_redem'] == '1') {
                    $total = UserWalletTotal::where('store_id', Auth::user()->store->id)->where('user_id', $order['User']['id'])->first();
                    $total->total_cashback = ($total->total_cashback + $order['wallet_amount_used']);
                    $total->save();
                    $this->orderRepo->where('id', $order['id'])->update(['wallet_amount_used' => 0,'wallet_redem'=>'0','total_amount' => ($order['total_amount'] + $order['wallet_amount_used'])]);
                }
                if ($order['refund_amount_used'] > 0 && $order['refund_amount_used'] != null) {
                    $total = UserWalletTotal::where('store_id', Auth::user()->store->id)->where('user_id', $order['User']['id'])->first();
                    $total->total_refund = ($total->total_refund + $order['refund_amount_used']);
                    $total->save();
                    $this->orderRepo->where('id', $order['id'])->update(['refund_amount_used' => 0, 'total_amount' => ($order['total_amount'] + $order['refund_amount_used'])]);
                }
                //end-cashback
                break;
            case (1):
                $message = 'has been accepted by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                break;
            case (2):
                $message = 'has been accepted by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['accepted_at' => date('Y-m-d H:i:s')]
                );
                //update stock
                $digitalLinks = [];
                $html = '<ol>';
                foreach ($order['subOrders'] as $OrderDetail) {

                    $stock = ProductStock::where('product_id', $OrderDetail->product_id)->first();
                    $stock->stock_value -= $OrderDetail->product_qty;
                    $stock->save();
                    if ($OrderDetail['product']['product_type'] == "2") {
                        $storeUrl=Auth::user()->store->domain==Null ? "https://".Auth::user()->store->subdomain.".".Config::get('constants.root_domain')."/digital-".$order['id'].'-pro-'.$OrderDetail['id'] : "https://".Auth::user()->store->domain_full_url."/digital-".$order['id'].'-pro-'.$OrderDetail['id'];
                        $html .= '<li><a href="'.$storeUrl.'" target="blank">'.$OrderDetail['product']['name_en'].'</li>';
                        array_push($digitalLinks,$storeUrl);

                    }
                }
                $html .= '</ol>';

                if (count($digitalLinks) > 0) {
                    $this->sendDigitalProducts($order['User'], $html, Auth::user()->store, $digitalLinks);
                     if ($order->order_type=="Digital Product"){
                            $this->orderRepo->where('id', $order->id)->update(['order_status' => '6']);
                     }
                }

                break;
            case (3):
                $message = 'status change to preparation by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['preparation_at' => date('Y-m-d H:i:s')]
                );
                break;
            case (4):
                $message = 'status change to ready  by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['ready_at' => date('Y-m-d H:i:s')]
                );
                $domainData=Auth::user()->store;
                sendSmsNotification($domainData,"your order number ".$order->code." is ready for delivery.\nThanks\n Team ".$domainData['store_name_en']."",$order['User']['phone']);
                break;
            case (5):
                $message = 'status change to dispatched by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['dispatched_at' => date('Y-m-d H:i:s')]
                );
                break;
            case (6):
                $message = 'status change to completed by ' . Auth::user()->first_name . ', Order ID: #' . $order->code;
                //Update Order Log
                $this->orderlog->updateOrCreate(
                    ['order_id' => $order->id],
                    ['complete_at' => date('Y-m-d H:i:s')]
                );
                // check wallet cashback,store_id,order data,cashback type ,user-id
                $domainData=Auth::user()->store;
                sendSmsNotification($domainData,"your order number ".$order->code." is delivered.\nThanks\n Team ".$domainData['store_name_en']."",$order['User']['phone']);
                StoreRewardOffer(Auth::user()->store->id, $order, 2, $order['User']['id']);
                break;
            default:
                $message = 'Status getting some problems, Order ID: #' . $order->code;
        }
        $body = [
            'body_text' => 'Order ' . $message,
            'body_text_arabic' => trans('menu_option.received_a_new_order'),
            'store_name_en' => $order->store->store_name_en,
            'store_name_ar' => $order->store->store_name_ar,
            'store_logo' => $order->store->store_logo_image,
            'link' =>  '/allOrders',
            'order_id' => $order->id
        ];

        $owners = [$order->store->user->id, 1];
        foreach ($owners as $userid) {

            $owner = User::find($userid);
            $owner->notify(new UserActivityNotification(Auth::user(), $order, $body));
        }
        return true;
    }
    public function acceptOrder($data)
    {
        try {
            DB::begintransaction();

            $fetchRequestedOrder = $this->orderRepo->with('subOrders')->where('id', $data['order_id'])->first();
            if ($fetchRequestedOrder) {


                if (Auth::user()->store["storeBaseCurrency"]["Currency"]->currency != $fetchRequestedOrder['currency']) {
                    $orderedCurrency = Currency::where('store_id', Auth::user()->store->id)->whereHas('Currency', function ($q) use ($fetchRequestedOrder) {
                        $q->where('currency', $fetchRequestedOrder['currency']);
                    })->first();
                    if ($orderedCurrency) {
                        $orderTotal = round($fetchRequestedOrder['total_amount'] / $orderedCurrency['exchange_rate'], 3);
                        $delivery = round($fetchRequestedOrder['delivery_cost'] / $orderedCurrency['exchange_rate'], 3);
                        $wallet_amount_used = round($fetchRequestedOrder['wallet_amount_used'] / $orderedCurrency['exchange_rate'], 3);
                        $refund_amount_used = round($fetchRequestedOrder['refund_amount_used'] / $orderedCurrency['exchange_rate'], 3);
                        Order::where('id', $fetchRequestedOrder->id)->update(['order_status' => $data['status'], 'payment_method' => '3', 'refund_amount_used' => $refund_amount_used, 'wallet_amount_used' => $wallet_amount_used, 'total_amount' => $orderTotal, 'delivery_cost' => $delivery, 'currency' => Auth::user()->store["storeBaseCurrency"]["Currency"]->currency]);
                        foreach ($fetchRequestedOrder['subOrders'] as $subOrder) {
                            $subOrder->product_price = round($subOrder->product_price / $orderedCurrency['exchange_rate'], 3);
                            $subOrder->save();
                        }
                    }
                } else {
                    $order = $this->orderRepo->where('id', $data['order_id'])->update(['order_status' => $data['status'], 'payment_method' => '3']);
                }
                //start-cashback

                if ($fetchRequestedOrder['wallet_redem'] == '1') {
                    $updatedorder = $this->orderRepo->where('id', $data['order_id'])->first();
                    $this->walletAmountUsed($updatedorder, 'cashback');
                }
                if ($fetchRequestedOrder['refund_amount_used'] > 0 && $fetchRequestedOrder['refund_amount_used'] != null) {
                    $updatedorder = $this->orderRepo->where('id', $data['order_id'])->first();
                    $this->walletAmountUsed($updatedorder, 'refund');
                }
                //end-cashback
                $this->orderlog->updateOrCreate(
                    ['order_id' => $fetchRequestedOrder->id],
                    ['accepted_at' => date('Y-m-d H:i:s')]
                );
                //update stock
                foreach ($fetchRequestedOrder['subOrders'] as $OrderDetail) {
                    $stock = ProductStock::where('product_id', $OrderDetail->product_id)->first();
                    $stock->stock_value -= $OrderDetail->product_qty;
                    $stock->save();
                }
                DB::commit();
                return array('status' => true, 'data' => 'true');
            } else {
                return array('status' => false, 'data' => 'false');
            }
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }

    public function walletAmountUsed($order, $type)
    {
        // decrement total amount
        $total = UserWalletTotal::where('store_id', Auth::user()->store->id)->where('user_id', $order['User']['id'])->first();
        if ($type == 'cashback') {
            if ($total->total_cashback >= $order['wallet_amount_used']) {
                $total->total_cashback = ($total->total_cashback - $order['wallet_amount_used']);
                $deduction = $order['wallet_amount_used'];
            } else {
                $updateOrder = Order::where('id', $order['id'])->first();
                $updateOrder->total_amount += $order['wallet_amount_used'] - $total->total_cashback;
                $updateOrder->wallet_amount_used = $total->total_cashback;
                $updateOrder->save();
                $total->total_cashback = ($total->total_cashback - $updateOrder['wallet_amount_used']);
                $deduction = $updateOrder['wallet_amount_used'];
            }
        } else {
            $total->total_refund = ($total->total_refund - $order['refund_amount_used']);
            $deduction = $order['refund_amount_used'];
        }
        if ($total->save()) {
            UserWallet::Create([
                'store_id' => Auth::user()->store->id,
                'user_id' => $order['User']['id'],
                'reward_id' => NULL,
                'order_id' => $order->id ?? Null,
                'type' => 'Cashback',
                'refund_type' => 1,
                'reson' => 'Used cashback for order #' . $order->code,
                'amount' => $deduction,
                'transaction_type' => '0'
            ]);
        }
    }

    public function cancelOrder($data)
    {
        try {
            DB::begintransaction();
            $order = $this->orderRepo->where('id', $data['order_id'])->update(['order_status' => $data['status']]);

            if ($order) {
                $order = $this->orderRepo->where('id', $data['order_id'])->first();
                $preOrder = $this->orderlog->where('order_id', $data['order_id'])->first();

                if ($preOrder) {

                    foreach ($order['subOrders'] as $OrderDetail) {
                        $stock = ProductStock::where('product_id', $OrderDetail->product_id)->first();
                        $stock->stock_value += $OrderDetail->product_qty;
                        $stock->save();
                    }
                    $this->orderlog->where('order_id', $data['order_id'])->update(['cancel_at' => date('Y-m-d H:i:s')]);
                } else {
                    $OrderLog = $this->orderlog->create(['order_id' => $data['order_id'], 'cancel_at' => date('Y-m-d H:i:s')]);
                }

            $domainData=Auth::user()->store;
            sendSmsNotification($domainData,"We are sorry to say that your order number ".$order['code']." has been canceled.\nThanks\nTeam".$domainData['store_name_en']."",$order['User']['phone']);

                DB::commit();
                return array('status' => true, 'data' => 'true');
            } else {
                return array('status' => false, 'data' => 'false');;
            }
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }

    public function addItem($data)
    {
        try {
            $dish = $this->dish->where('id', $data['product_id'])->first();

            DB::begintransaction();

            if (isset($data['extraoption'])) {
                $option = $data['extraoption'];
                $newItem = $this->subOrder->create(['order_id' => $data['order_id'], 'product_id' => $dish->id, 'product_name' => $dish->name_en, 'product_price' => $data['product_price'], 'product_qty' => $data['product_qty'], 'product_options' => json_encode($option)]);
            } else {
                $option = [];
                $newItem = $this->subOrder->create(['order_id' => $data['order_id'], 'product_id' => $dish->id, 'product_name' => $dish->name_en, 'product_price' => ($data['product_qty'] * $dish->price), 'product_qty' => $data['product_qty'], 'product_options' => json_encode($option)]);
            }
            $order = $this->orderRepo->where('id', $data['order_id'])->with('subOrders')->first();
            $orderTotal = 0;
            foreach ($order['subOrders'] as $item) {
                $orderTotal += $item['product_price'];
            }
            $order->total_amount = $orderTotal + $order->delivery_cost;
            $order->total_no_of_items = count($order['subOrders']);
            $order->save();

            DB::commit();
            return array('status' => true, 'data' => $order, 'message' => 'Item Updated Successfully.');
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage);
        }
    }



    public function createOrderPickedUpForm($order)
    {
        $response = [];
        $newData = '';
        $subtotal = 0;
        $total = 0;

            $data = '
                <div class="row">
                    <div class="col-md-8">
                        <div class="info-boxx printable" id="printable">
                            <h3>' . trans('menu_option.invoice') . '</h3>
                            <img alt="Frybury" width="70" class="" src="' .asset('/Frybury-logo.png'). '">


                            <p><span class="left-span">' . trans('menu_option.customer') . ':</span><span class="right-span">' . $order['User']['nmae'] . '</span></p>
                            <p><span class="left-span">' . trans('menu_option.phone') . ':</span><span class="right-span">' . $order['User']['phone'] . '</span></p>
                            <p><span class="left-span">' . trans('menu_option.area') . ':</span><span class="right-span">' . $order['address']['area'] . '</span></p>
                            <p><span class="left-span">' . trans('menu_option.address') . ':</span><span class="right-span">' . $order['address']['address'] . '</span></p>
                            <p><span class="left-span">' . trans('menu_option.notes') . ':</span><span class="right-span">' . $order['note_for_restaurant'] . '</span></p>
                            <p><span class="left-span">' . trans('menu_option.email') . ':</span><span class="right-span">' . $order['User']['email'] . '</span></p>
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                    <th>' . trans('menu_option.qty') . '</th>
                                    <th>' . trans('menu_option.item') . '</th>
                                    <th>' . trans('menu_option.price') . '</th>
                                    </tr>';

            foreach ($order['subOrders'] as $singData) {
                $subtotal += $singData['product_price'];
                $newData .= '<tr>
                                            <td>' . $singData['product_qty'] . '</td>
                                            <td>' . $singData['product_name'] .'</td>
                                            <td>' . $singData['product_price'] .'</td>
                                            </tr>';
            }

            $delivery = $order['shipping_charges'] ?? 0;
            $total = $order['total_amount'];
            $wallet = $order['wallet_amount_used'] ?? 0;

            $newData2 = '
                                    <tr>
                                        <th>' . trans('menu_option.subtotal') . '</th>
                                        <th></th>
                                        <th> Rs ' . $subtotal .'</th>
                                    </tr>
                                    <tr>
                                        <td>' . trans('menu_option.delivery_fee') . '</td>
                                        <td></td>
                                        <td> + ' . $delivery .'</td>
                                    </tr>';


            $newData2 .= '<tr>
                                        <th>' . trans('menu_option.total') . '</th>
                                        <th></th>
                                        <th> Rs ' . $total .'</th>
                                    </tr>
                                       <tr>
                                        <td>' . trans('menu_option.payment') . '</td>
                                        <td></td>
                                        <td>' . $order->payment_method . '</td>
                                    </tr>';


            $newData2 .= '</tbody>
                            </table>
                        </div>
                        <div class="info-footer">';
            if ($order['order_status'] == 1 || $order['order_status'] == 9) {
                $newData2 .= '

                                        <button class="btn btn-default btn-sm btn-accept" id="accept_order" style="margin-left: 30rem;" data-id="' . $order['id'] . '" data-status="2" data-toggle="tooltip" data-original-title="Click here for accepte this order">' . trans('menu_option.accept_order') . '</button>';
            };
            if ($order['order_status'] == 2) {
                $newData2 .= '
                                        <button type="button" class="btn btn-cancel changeOrderStatus" data-value="4_' . $order->id . '">' . trans('menu_option.cancel_order') . '</button>
                                        <button class="btn btn-primary btn-sm btn-accept changeOrderStatus" data-value="3_' . $order->id . '" data-status="3" data-toggle="tooltip" data-original-title="Click here for make order status in preparation">' . trans('menu_option.preparation') . '</button> ';
            };
            if ($order['order_status'] == 3) {
                $newData2 .= '
                                        <button type="button" class="btn btn-cancel changeOrderStatus" data-value="4_' . $order->id . '">' . trans('menu_option.cancel_order') . '</button>
                                        <button class="btn btn-primary btn-sm btn-accept changeOrderStatus" data-value="4_' . $order->id . '" data-status="4" data-toggle="tooltip" data-original-title="Click here for make order status is ready">' . trans('menu_option.ready') . '</button> ';
            };
            if ($order['order_status'] == 4) {
                $newData2 .= '
                                        <button type="button" class="btn btn-cancel changeOrderStatus" data-value="4_' . $order->id . '">' . trans('menu_option.cancel_order') . '</button>
                                        <button class="btn btn-success btn-sm btn-accept changeOrderStatus" data-value="6_' . $order->id . '" data-status="6" data-toggle="tooltip" data-original-title="Click here for make order status is dispatched from store">' . trans('menu_option.dispatch') . '</button> ';
            };
            if ($order['order_status'] == 5) {
                $newData2 .= '
                                        <button type="button" class="btn btn-cancel changeOrderStatus" data-value="4_' . $order->id . '">' . trans('menu_option.cancel_order') . '</button>
                                        <button class="btn btn-success btn-sm btn-accept changeOrderStatus" data-value="6_' . $order->id . '" data-status="6" data-toggle="tooltip" data-original-title="Click here for make order status is delivered to customer">' . trans('menu_option.delivered') . '</button>';
            };
            $newData2 .= '
                        </div>
                    </div>
                    <div class="col-md-4">

                        <div class="print-boxx">
                            <h3>' . trans('menu_option.print') . '</h3>
                            <div class="inner-box">
                                <button class="btn btn-print" id="printit">' . trans('menu_option.print') . '</button>
                            </div>
                        </div>
                        <div class="print-boxx">
                            <div class="inner-box text-center">
                                <a class="btn  btn-success" href="https://api.whatsapp.com/send?text=Order No :  ' . $order->code . ' ,Customer Name : ' . $order->user->name . ' ,Phone Number: ' . $order->user->phone . ', Total: ' . $total . ', Address :  ' . $order->address->address . '" target="_blank"><i class="fab fa-whatsapp"></i>' . trans('menu_option.send_via_whatsapp') . '</a>
                            </div>
                        </div>
                    </div>
                </div>';

        $response['data'] = $data;
        $response['newData'] = $newData;
        $response['newData2'] = $newData2;
        return $response;
    }



    public function productOptionView($order)
    {
        $options = json_decode($order['product_options']);
        $html = '<ol>';
        if (count($options) > 0) {
            foreach ($options as $singleoption) {

                $html .= '<li>' . $singleoption->option->option_name . ': <b>' . $singleoption->extra_option_name . '</b></li>';
            };
        }
        $html .= '</ol>';
        return $html;
    }


    public function sendDigitalProducts($user, $links, $store, $smsLinks)
    {
        if (isset($user->email)) {
            $email_template = MailTemplate::where('id', 7)->first();
            $string_to_replace = array("{FULL_NAME}", "{LINKS}", "{STORE_NAME}");
            $content = str_replace($string_to_replace, [$user->full_name, $links, $store->store_name_en], $email_template->mail_body_en);
            $data['email'] = $user->email;
            $data['subject'] = $email_template->subject;
            $data['content'] = $content;

            mailSend($data);
        }
        $smsLinks=implode(',\n',$smsLinks);
        $domainData=$store;
        $description="Hello ".$user->full_name."\n Please find the attached links for your ordered products.\n".$smsLinks." \n Thanks \n".$store->store_name_en;
        sendSmsNotification($domainData,$description,$user['phone']);


    }
}
