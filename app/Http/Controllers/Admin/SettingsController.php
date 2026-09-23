<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\MarketplaceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use App\Services\MarketplaceEmail;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    private array $secretFields = [
        'r2' => ['access_key_id', 'secret_access_key'],
        'mail' => ['gmail_client_id','gmail_client_secret','gmail_refresh_token'],
        'payments' => ['stripe_secret_key', 'stripe_webhook_secret', 'stripe_connect_webhook_secret', 'razorpay_key_secret', 'razorpay_webhook_secret', 'paypal_client_secret', 'paypal_webhook_id'],
    ];

    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'superadmin', 403, 'Superadmin access required.');
    }

    public function index(Request $request)
    {
        $this->authorizeSuperadmin($request);

        return response()->json([
            'general' => $this->visible('general'),
            'r2' => $this->visible('r2'),
            'mail' => $this->visible('mail'),
            'payments' => $this->visible('payments'),
            'billing' => $this->visible('billing'),
            'webhook_urls' => $this->webhookUrls($request),
        ]);
    }

    public function update(Request $request, string $group)
    {
        $this->authorizeSuperadmin($request);
        abort_unless(in_array($group, ['general', 'r2', 'mail', 'payments', 'billing'], true), 404);

        $rules = match ($group) {
            'general' => [
                'marketplace_name' => ['nullable', 'string', 'max:120'],
                'support_email' => ['nullable', 'email', 'max:255'],
                'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            'r2' => [
                'access_key_id' => ['nullable', 'string', 'max:255'],
                'secret_access_key' => ['nullable', 'string', 'max:255'],
                'bucket' => ['nullable', 'string', 'max:255'],
                'endpoint' => ['nullable', 'url', 'max:500'],
                'public_url' => ['nullable', 'url', 'max:500'],
            ],
            'payments' => [
                'stripe_enabled' => ['nullable', 'boolean'],
                'razorpay_enabled' => ['nullable', 'boolean'],
                'paypal_enabled' => ['nullable', 'boolean'],
                'currency' => ['nullable', 'in:USD,INR'],
                'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
                'stripe_secret_key' => ['nullable', 'string', 'max:255'],
                'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
                'stripe_connect_enabled' => ['nullable', 'boolean'],
                'stripe_connect_account_type' => ['nullable', 'in:express'],
                'stripe_connect_default_country' => ['nullable', 'size:2'],
                'stripe_connect_webhook_secret' => ['nullable', 'string', 'max:255'],
                'stripe_connect_transfers_enabled' => ['nullable', 'boolean'],
                'razorpay_key_id' => ['nullable', 'string', 'max:255'],
                'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
                'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
                'paypal_client_id' => ['nullable', 'string', 'max:255'],
                'paypal_client_secret' => ['nullable', 'string', 'max:255'],
                'paypal_webhook_id' => ['nullable', 'string', 'max:255'],
                'paypal_mode' => ['nullable', 'in:sandbox,live'],
            ],
            'billing' => [
                'legal_name' => ['nullable', 'string', 'max:160'],
                'address_line_1' => ['nullable', 'string', 'max:255'],
                'address_line_2' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:120'],
                'country' => ['nullable', 'string', 'max:120'],
                'tax_id' => ['nullable', 'string', 'max:120'],
                'invoice_prefix' => ['nullable', 'string', 'max:20'],
                'tax_mode' => ['nullable', 'in:none,inclusive,exclusive'],
                'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'payout_hold_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'support_months' => ['nullable', 'integer', 'min:0', 'max:60'],
                'support_extension_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'support_renewal_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'handling_fee_enabled' => ['nullable', 'boolean'],
                'handling_fee_low_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
                'handling_fee_low_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
                'handling_fee_mid_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
                'handling_fee_mid_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            ],
            'mail' => [
                'gmail_client_id' => ['nullable', 'string', 'max:255'],
                'gmail_client_secret' => ['nullable', 'string', 'max:255'],
                'gmail_refresh_token' => ['nullable', 'string', 'max:2000'],
                'provider' => ['nullable', 'in:gmail_api'],
                'from_address' => ['nullable', 'email', 'max:255'],
                'from_name' => ['nullable', 'string', 'max:255'],
            ],
        };

        $values = $request->validate($rules);

        if ($group === 'payments') {
            foreach (['stripe_publishable_key' => 'pk_', 'stripe_secret_key' => 'sk_'] as $field => $prefix) {
                $value = $values[$field] ?? null;
                if (!empty($value) && $value !== '********' && !str_starts_with($value, $prefix)) {
                    throw ValidationException::withMessages([$field => [ucwords(str_replace('_', ' ', $field)).' must begin with '. $prefix]]);
                }
            }
        }
        $record = ApplicationSetting::firstOrNew(['group' => $group]);
        $existing = $record->payload ?? [];

        foreach ($this->secretFields[$group] ?? [] as $key) {
            if (empty($values[$key]) || $values[$key] === '********') {
                unset($values[$key]);
            }
        }

        $record->payload = array_filter(
            array_merge($existing, $values),
            fn ($value) => $value !== null && $value !== ''
        );
        $record->save();

        return response()->json([
            'message' => ucfirst($group).' settings saved.',
            $group => $this->visible($group),
        ]);
    }

    public function testMail(Request $request)
    {
        $this->authorizeSuperadmin($request);
        $request->validate(['to' => ['required', 'email']]);

        MarketplaceEmail::queue($request->to, 'Marketplace mail settings test', '<p>This is a marketplace settings test email.</p>');
        return response()->json(['message' => 'Test email queued.']);
    }

    private function webhookUrls(Request $request): array
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/').'/api/payments';
        return ['stripe' => $base.'/stripe/webhook', 'razorpay' => $base.'/razorpay/webhook', 'paypal' => $base.'/paypal/webhook'];
    }

    private function visible(string $group): array
    {
        $value = ApplicationSetting::where('group', $group)->value('payload') ?? [];

        foreach ($this->secretFields[$group] ?? [] as $key) {
            $value[$key] = !empty($value[$key]) ? '********' : '';
        }

        return $value;
    }
}
