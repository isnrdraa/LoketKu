<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Ambil nilai setting berdasarkan key, dengan default fallback.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?? $default;
    }

    /**
     * Set atau update nilai setting.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Ambil semua setting toko sebagai array.
     */
    public static function store(): array
    {
        return [
            'name'    => static::get('store_name', config('app.name')),
            'address' => static::get('store_address', config('app.store_address', '')),
            'phone'   => static::get('store_phone', config('app.store_phone', '')),
            'footer'  => static::get('store_footer', config('app.store_footer', 'Terima kasih!')),
        ];
    }
}
