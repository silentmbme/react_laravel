<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Storage;

class MarketplaceSettings
{
    public function group(string $group): array
    {
        return ApplicationSetting::where('group', $group)->value('payload') ?? [];
    }

    public function r2Config(): array
    {
        $saved = $this->group('r2');
        $base = config('filesystems.disks.r2');

        return array_filter([
            'driver' => 's3',
            'key' => $saved['access_key_id'] ?? $base['key'],
            'secret' => $saved['secret_access_key'] ?? $base['secret'],
            'region' => 'auto',
            'bucket' => $saved['bucket'] ?? $base['bucket'],
            'endpoint' => $saved['endpoint'] ?? $base['endpoint'],
            'use_path_style_endpoint' => true,
            'throw' => false,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function r2Disk()
    {
        return Storage::build($this->r2Config());
    }

    public function r2Bucket(): ?string
    {
        return $this->r2Config()['bucket'] ?? null;
    }
}
