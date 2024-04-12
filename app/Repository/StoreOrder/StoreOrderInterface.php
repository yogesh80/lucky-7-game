<?php

namespace App\Repository\StoreOrder;

interface StoreOrderInterface
{
    public function checkStatus($request);

    public function addUserDetails($request);

    public function placeOrder($request,$domainData);
}
