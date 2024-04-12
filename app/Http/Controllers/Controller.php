<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
Use Alert;
use Session;
use View;
use Toastr;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
     protected $active_store_id;

    public function __construct()
    {
        // Fetch the Site Settings object

        $this->active_store_id = session()->get('active_store_id');
        View::share('active_store_id', $this->active_store_id);
    }
}
