<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Section extends Model
{
    use HasFactory;

    public const SLUG_PUBLICIDADE = 'publicidade';
    public const SLUG_OOH = 'ooh';
    public const SLUG_DOCUMENTARIOS = 'documentarios';
    public const SLUG_NATUREZA = 'natureza';

    protected $fillable = [
        'slug',
        'title',
        'description_text',
        'video_webm_desktop',
        'video_mp4_desktop',
        'video_webm_mobile',
        'video_mp4_mobile',
        'processing_status',
        'processing_error',
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
