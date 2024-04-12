<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\StoreEmail;

class OrderStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
public $subject;
    public $data,$mailfor;
    /**
     * Create a new job instance.
     *
     * @return void
     */
   public function __construct($subject,$data,$mailfor)
    {
        $this->subject=$subject;
        $this->mailfor=$mailfor;
        $this->data=$data;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
            $subject = !empty($data['subject']) ? $data['subject'] : '';
             Mail::to($data['email'])->send(new SendEmail($subject, $data));
         
            
    }
}
