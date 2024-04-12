<?php
namespace App\Traits;

use Illuminate\Support\Collection;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use DB;
use Image;
use Storage;

use File;

trait ImageUpload {

	public function fileUpload($file, $filePath){

		if ($file) {

		try {
			
        $image = $file;
        $filename    = time().rand(100000,10000000).'.'.strtolower($image->getClientOriginalExtension());
         $image->move(public_path().'/webImages/',$filename);
         $path=env("APP_URL","https://www.Frybury.com").'/webImages/'.$filename;
		 return $path;
		
		} catch (\Throwable $th) {

			throw $th;
		}
		}
		return false;
	}

}
