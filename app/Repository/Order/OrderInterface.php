<?php

namespace App\Repository\Order;

interface OrderInterface
{
    public function getAll();

    public function show($request);

    public function changeStatus($request);

    public function acceptOrder($request);

    public function cancelOrder($request);

    public function addItem($request);
    
}
