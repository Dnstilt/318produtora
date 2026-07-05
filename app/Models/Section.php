<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Section extends Model
{
    use HasFactory;

    public const SLUG_HOME = 'home';
    public const SLUG_OOH = 'ooh';
    public const SLUG_EVENTOS = 'eventos';
    public const SLUG_OQUEMAISFAZEMOS = 'oque-mais-fazemos';

    protected $fillable = [
        'slug',
        'title',
        'description_text',
        'video_public_id',
        'video_webm_desktop',
        'video_mp4_desktop',
        'video_webm_mobile',
        'video_mp4_mobile',
        'processing_status',
        'processing_error',
        'mobile_video_public_id',
        'mobile_processing_status',
        'mobile_processing_error',
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
