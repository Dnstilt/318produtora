<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SocialLink extends Model
{
    use HasFactory;

    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_LINKEDIN = 'linkedin';
    public const PLATFORM_TIKTOK = 'tiktok';

    protected $fillable = [
        'platform',
        'url',
        'icon_class',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->updated_at = Carbon::now();
        });
    }
}
