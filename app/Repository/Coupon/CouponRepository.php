<?php

namespace App\Repository\Coupon;

use App\Models\Coupon;
use Auth;
use Gate;
use DB;

class CouponRepository implements CouponInterface
{
    /**
     * @var Coupon
     */
    private $couponRepo;

    /**
     * CouponRepository constructor.
     *
     * @param Coupon $couponRepo
     */
    public function __construct(Coupon $couponRepo)
    {
        $this->couponRepo = $couponRepo;
    }

    public function getAll()
    {
        $vouchers = $this->couponRepo->get();
         return array('status' => true, 'data' => $vouchers);


    }

    public function store($data)
    {
        try {
            $objCoupon = $this->couponRepo;

            DB::begintransaction();
            $this->buildVoucherObject($data, $objCoupon);
            $response = $objCoupon->save();
            if($response) {
                DB::commit();
                return array('status' => true, 'message' => trans('menu_option.voucher_added_success'));
            }
            return array('status' => false, 'message' => 'Something went wrong.');
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage());
        }
    }

    public function update($data, $id)
    {
        try {
            $objCoupon = $this->couponRepo->findorfail($id);
            DB::begintransaction();
            $this->buildVoucherObject($data, $objCoupon);
            $response = $objCoupon->save();
            if($response) {
                DB::commit();
                return array('status' => true, 'message' => trans('menu_option.voucher_added_success'));
            }
            return array('status' => false, 'message' => 'Something went wrong.');
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $coupon = $this->couponRepo->where('id', $id)->delete();
            if($coupon) {
                return array('status' => true, 'message' => trans('menu_option.voucher_delete'));
            }
            return array('status' => false, 'message' => 'Something went wrong.');
        } catch (Exception $e) {
            return array('status' => false, 'message' => $e->getMessage());
        }
    }

    public function buildVoucherObject($data, $objCoupon)
    {

        $objCoupon->voucher_name = $data['voucher_name'];
        $objCoupon->free_delivery = isset($data['free_delivery']) ? '1' : '0';
        $objCoupon->discount_type = $data['discount_type'];
        $objCoupon->discount = $data['discount'];
        $objCoupon->voucher_start_date = date('Y-m-d h-i-s', strtotime($data['voucher_start_date']));
        $objCoupon->voucher_end_date =  date('Y-m-d h-i-s', strtotime($data['voucher_end_date']));
        $objCoupon->max_redemption = $data['max_redemption'];
        $objCoupon->min_order_amount = $data['min_order_amount'];
    }
}
