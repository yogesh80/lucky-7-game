<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Facebookconversion;
class FacebookConversionApiEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $fbService,$eventData,$apiData;
    public function __construct($eventData,$apiData)
    {
        
        $this->eventData=$eventData;
        $this->apiData=$apiData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Facebookconversion $fbService)
    {
        $fbService->postWebEvent($this->eventData,$this->apiData);
    }
}
