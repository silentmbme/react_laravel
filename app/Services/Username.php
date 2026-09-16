<?php

namespace App\Services;

use Illuminate\Validation\Rule;

final class Username
{
    public const RESERVED = ['admin','administrator','api','auth','author','cart','checkout','customer','help','login','logout','marketplace','notifications','null','profile','purchases','register','reviewer','root','settings','staff','support','system','undefined','users'];
    public static function normalize(mixed $value): string { return strtolower(trim((string) $value)); }
    public static function isFormatValid(string $username): bool { return (bool) preg_match('/^[a-z0-9][a-z0-9_-]{2,31}$/', $username); }
    public static function isReserved(string $username): bool { return in_array($username, self::RESERVED, true); }
    public static function rules(?int $ignoreUserId = null): array { $unique = Rule::unique('users', 'username'); if ($ignoreUserId) $unique->ignore($ignoreUserId); return ['required','string','min:3','max:32','regex:/^[a-z0-9][a-z0-9_-]*$/',Rule::notIn(self::RESERVED),$unique]; }
}