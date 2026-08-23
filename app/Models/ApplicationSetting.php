<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    protected $fillable = ['group', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array'];
    }
}
