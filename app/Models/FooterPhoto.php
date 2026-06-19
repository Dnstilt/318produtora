<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FooterPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'photo_avif',
        'photo_webp',
        'photo_jpg',
        'order',
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

        static::deleting(function (self $model): void {
            // Delete physical files when model is deleted
            \Illuminate\Support\Facades\Storage::disk('public')->delete([
                $model->photo_avif,
                $model->photo_webp,
                $model->photo_jpg,
            ]);
        });
    }
}
