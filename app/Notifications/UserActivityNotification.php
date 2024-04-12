<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivityNotification extends Notification
{
    use Queueable;
    private $user, $order, $additionalData;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $order,$additionalData)
    {
        $this->user = $user;
        $this->order = $order;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'username' => $this->order->user->first_name,
            'profile_image' =>$this->order->user->first_name ?? '',
            'body_text' => $this->additionalData['body_text'],
            'body_text_arabic' => $this->additionalData['body_text_arabic'],
            'store_name_en' => $this->additionalData['store_name_en'],
            'store_name_ar' => $this->additionalData['store_name_ar'],
            'store_logo' => $this->additionalData['store_logo'],
            'link' => $this->additionalData['link'],
            'order_id' => $this->order->id,
        ];
    }
}
