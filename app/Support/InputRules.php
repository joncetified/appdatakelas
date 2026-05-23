<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class InputRules
{
    /**
     * @return list<mixed>
     */
    public static function humanName(int $max = 80, bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:2',
            "max:{$max}",
            'no_long_words:30',
            "regex:/\A[\pL\pM\pN\s.,'()\-]+\z/u",
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function phone(bool $required = false, int $minDigits = 8): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:16',
            "regex:/\A\+?[0-9]{{$minDigits},15}\z/",
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function password(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:72',
            'confirmed',
            Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function safeText(int $max, bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            "max:{$max}",
            'no_long_words:30',
            'not_regex:/[<>=]/',
        ];
    }

    /**
     * @return list<mixed>
     */
    public static function brandName(): array
    {
        return [
            'required',
            'string',
            'max:40',
            'no_long_words:24',
            'not_regex:/[<>=]/',
        ];
    }
}
