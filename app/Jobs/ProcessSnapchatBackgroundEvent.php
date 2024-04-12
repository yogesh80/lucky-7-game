<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSnapchatBackgroundEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    private $eventData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($eventData)
    {
        $this->eventData=$eventData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       return sendSnapChatConversionEvent($this->eventData);
    }
}
