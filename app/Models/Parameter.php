<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Parameter extends Model
{
    protected $primaryKey = 'kode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kode', 'nilai', 'keterangan'];

    public static function nilai(string $kode, mixed $default = null): mixed
    {
        $all = Cache::remember('parameters.all', 300, fn () => static::query()->pluck('nilai', 'kode')->all());
        return $all[$kode] ?? $default;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('parameters.all'));
        static::deleted(fn () => Cache::forget('parameters.all'));
    }
}
