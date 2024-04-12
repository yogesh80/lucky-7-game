<?php

namespace App\Repository\Coupon;

interface CouponInterface
{
    public function getAll();

    public function store($request);

    public function update($request, $id);

    public function delete($id);
}
