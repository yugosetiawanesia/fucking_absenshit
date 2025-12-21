<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getString(string $key, ?string $default = null): ?string
    {
        /** @var self|null $setting */
        $setting = self::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::getString($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setString(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public static function setBool(string $key, bool $value): void
    {
        self::setString($key, $value ? '1' : '0');
    }
}
