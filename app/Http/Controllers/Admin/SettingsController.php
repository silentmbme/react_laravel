<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\MarketplaceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    private array $secretFields = [
        'r2' => ['access_key_id', 'secret_access_key'],
        'mail' => ['password'],
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
        ]);
    }

    public function update(Request $request, string $group)
    {
        $this->authorizeSuperadmin($request);
        abort_unless(in_array($group, ['general', 'r2', 'mail'], true), 404);

        $rules = match ($group) {
            'general' => [
                'marketplace_name' => ['nullable', 'string', 'max:120'],
                'support_email' => ['nullable', 'email', 'max:255'],
            ],
            'r2' => [
                'access_key_id' => ['nullable', 'string', 'max:255'],
                'secret_access_key' => ['nullable', 'string', 'max:255'],
                'bucket' => ['nullable', 'string', 'max:255'],
                'endpoint' => ['nullable', 'url', 'max:500'],
                'public_url' => ['nullable', 'url', 'max:500'],
            ],
            'mail' => [
                'mailer' => ['nullable', 'in:smtp,log,array'],
                'host' => ['nullable', 'string', 'max:255'],
                'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:255'],
                'encryption' => ['nullable', 'in:tls,ssl,null'],
                'from_address' => ['nullable', 'email', 'max:255'],
                'from_name' => ['nullable', 'string', 'max:255'],
            ],
        };

        $values = $request->validate($rules);
        $record = ApplicationSetting::firstOrNew(['group' => $group]);
        $existing = $record->payload ?? [];

        foreach ($this->secretFields[$group] ?? [] as $key) {
            if (empty($values[$key]) || $values[$key] === '••••••••') {
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

        $settings = (new MarketplaceSettings())->group('mail');
        Config::set('mail.default', $settings['mailer'] ?? config('mail.default'));
        Config::set('mail.mailers.smtp.host', $settings['host'] ?? config('mail.mailers.smtp.host'));
        Config::set('mail.mailers.smtp.port', $settings['port'] ?? config('mail.mailers.smtp.port'));
        Config::set('mail.mailers.smtp.username', $settings['username'] ?? config('mail.mailers.smtp.username'));
        Config::set('mail.mailers.smtp.password', $settings['password'] ?? config('mail.mailers.smtp.password'));
        Config::set('mail.mailers.smtp.scheme', ($settings['encryption'] ?? null) === 'ssl' ? 'smtps' : null);
        Config::set('mail.from.address', $settings['from_address'] ?? config('mail.from.address'));
        Config::set('mail.from.name', $settings['from_name'] ?? config('mail.from.name'));

        Mail::raw('This is a marketplace settings test email.', function ($message) use ($request) {
            $message->to($request->to)->subject('Marketplace mail settings test');
        });

        return response()->json(['message' => 'Test email sent.']);
    }

    private function visible(string $group): array
    {
        $value = ApplicationSetting::where('group', $group)->value('payload') ?? [];

        foreach ($this->secretFields[$group] ?? [] as $key) {
            $value[$key] = !empty($value[$key]) ? '••••••••' : '';
        }

        return $value;
    }
}
