<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class UserSettingsCast implements CastsAttributes
{

    private const DEFAULTS = [
        'language' => 'english',
        'theme' => 'dark',
        'email_notifications' => false,
        'push_notifications' => false,
        '2FA' => false,
    ];

    /**
     * Cast the given value.
     *
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return array_replace(
            self::DEFAULTS,
            json_decode($value ?? '{}', true)
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode(
            array_replace(self::DEFAULTS, $value)
        );
    }
}
