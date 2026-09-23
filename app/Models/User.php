<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Services\MarketplaceEmail;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
        'freelance_enabled',
        'freelance_url',
        'payout_method',
        'payout_destination',
        'payout_destination_last4',
        'tax_residency_country',
        'tax_form_type',
        'tax_form_status',
        'tax_form_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['is_staff', 'admin_access', 'review_access'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected static function booted(): void { static::creating(function (self $user) { if (filled($user->username)) return; $base=Str::of($user->email ?: $user->name ?: "member")->before("@")->lower()->replaceMatches("/[^a-z0-9_-]/", "-")->trim("-"); $user->username=Str::substr(($base->length() >= 3 ? $base : Str::of("member"))."-".Str::lower(Str::random(7)),0,32); }); }
        public function permissions(){ return $this->hasMany(UserPermission::class); }
    public function customRole(){ return $this->belongsTo(CustomRole::class, 'role', 'slug')->where('is_active', true); }
    public function isStaff(): bool { return in_array($this->role, ['superadmin', 'admin', 'reviewer'], true) || (bool) $this->customRole?->is_staff; }
    public function hasPermission(string $permission): bool { if ($this->role === 'superadmin') return true; if ($permission === 'admin.access' && $this->role === 'admin') return true; if ($permission === 'review.products' && in_array($this->role, ['admin', 'reviewer'], true)) return true; if ($this->permissions()->where('permission', $permission)->exists()) return true; return in_array($permission, $this->customRole?->permissions ?? [], true); }
    public function getIsStaffAttribute(): bool { return $this->isStaff(); }
    public function getAdminAccessAttribute(): bool { return $this->hasPermission('admin.access'); }
    public function getReviewAccessAttribute(): bool { return $this->hasPermission('review.products'); }
    public function sendPasswordResetNotification($token): void { $url=rtrim(config("app.frontend_url", config("app.url")),"/")."/reset-password?token=".urlencode($token)."&email=".urlencode($this->email); MarketplaceEmail::queue($this->email,"Reset your MarketPlace password","<p>Hello ".e($this->name).",</p><p>We received a request to reset your password.</p><p><a href=\"".e($url)."\">Reset your password</a></p><p>This link expires in 60 minutes. If you did not request it, you can ignore this email.</p>"); }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'payout_destination' => 'encrypted',
            'tax_form_completed_at' => 'datetime',
        ];
    }
}
