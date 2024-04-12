<?php

namespace App\Repository\Home;

interface HomeInterface
{
    public function pagViewEventForConversion($data);

    public function showReadOnly();

    public function showReadOnlyDetails($id);

    public function search($data);

    public function show($slug);

    public function branch();

    public function showBranch($id);

    public function orderMode();

    public function orderSuccess($data, $code);

    public function orderFailed($data, $code);

    public function yourorders();

    public function webEventFB($eventName,$userData,$customData='null');


}
