<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'group', 'value', 'description'];

    /**
     * Get a setting value by key, with an optional default.
     */
    public static function get(string $key, $default = null, ?string $group = 'system')
    {
        $setting = self::where('group', $group ?? 'system')
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value, ?string $description = null, ?string $group = 'system')
    {
        return self::updateOrCreate(
            [
                'group' => $group ?? 'system',
                'key' => $key,
            ],
            [
                'value' => $value,
                'description' => $description ?? \Illuminate\Support\Facades\DB::raw('description')
            ]
        );
    }
}
