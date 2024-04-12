<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------

*/

Route::group(['namespace' => 'Web'], function () {
      Route::get('/','gameController@index');
      Route::get('/reset','gameController@reset');
      Route::post('/submit-bet','gameController@submit');
});
?>







