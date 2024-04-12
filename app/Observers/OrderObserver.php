<?php

namespace App\Observers;
use App\Notifications\UserActivityNotification;
use App\Models\Order;
use App\Models\SubOrder;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Cache;
class OrderObserver
{
    public function created(Order $order)
    {


    }


    public function updated(Order $order){



     }
}
