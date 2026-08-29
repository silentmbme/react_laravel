<?php
namespace App\Services;
use Illuminate\Support\Facades\Config;
class MarketplaceMailer {
 public function apply(): void {
  $settings=(new MarketplaceSettings())->group('mail');
  Config::set('mail.default',$settings['mailer']??config('mail.default'));
  Config::set('mail.mailers.smtp.host',$settings['host']??config('mail.mailers.smtp.host'));
  Config::set('mail.mailers.smtp.port',$settings['port']??config('mail.mailers.smtp.port'));
  Config::set('mail.mailers.smtp.username',$settings['username']??config('mail.mailers.smtp.username'));
  Config::set('mail.mailers.smtp.password',$settings['password']??config('mail.mailers.smtp.password'));
  Config::set('mail.mailers.smtp.scheme',($settings['encryption']??null)==='ssl'?'smtps':null);
  Config::set('mail.from.address',$settings['from_address']??config('mail.from.address'));
  Config::set('mail.from.name',$settings['from_name']??config('mail.from.name'));
 }
}