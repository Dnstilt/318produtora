<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Page extends Model
{
    use HasFactory;

    public const SLUG_TERMOS = 'termos';
    public const SLUG_PRIVACIDADE = 'privacidade';

    protected $fillable = [
        'slug',
        'content',
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
