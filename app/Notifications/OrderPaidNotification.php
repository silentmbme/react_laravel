<?php
namespace App\Notifications;
use App\Models\Order;
use App\Services\MarketplaceMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
class OrderPaidNotification extends Notification implements ShouldQueue {
 use Queueable;
 public function __construct(public int $orderId) {}
 public function via(object $notifiable): array { return ['database']; } public function toArray(object $notifiable): array { $order=Order::query()->findOrFail($this->orderId); return ['title'=>'Order confirmed','body'=>'Your payment for order #'.substr($order->public_id,0,8).' was confirmed. Your downloads are ready.','url'=>'/purchases','kind'=>'purchase']; }
 public function toMail(object $notifiable): MailMessage {
  app(MarketplaceMailer::class)->apply();
  $order=Order::query()->findOrFail($this->orderId);
  return (new MailMessage)->subject('Your MarketPlace order is confirmed')->greeting('Hello '.$notifiable->name.',')->line('Your payment for order #'.substr($order->public_id,0,8).' has been confirmed.')->line('Total: '.strtoupper($order->currency).' '.number_format((float)$order->total,2))->action('View your downloads',rtrim(config('app.url'),'/').'/purchases')->line('Thank you for your purchase.');
 }
}