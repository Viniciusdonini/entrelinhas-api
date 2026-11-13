<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HttpsCoverUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $valid = is_string($value) && ! preg_match('~[\s\\\\]~u', $value);
        try {
            $parts = $valid ? parse_url($value) : false;
        } catch (\ValueError) {
            $parts = false;
        }
        $hostPattern = '~^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$~iD';

        if (! is_array($parts)
            || strcasecmp($parts['scheme'] ?? '', 'https') !== 0
            || ! preg_match($hostPattern, $parts['host'] ?? '')
            || isset($parts['user']) || isset($parts['pass'])) {
            $fail('Informe um link HTTPS válido para a capa, sem espaços ou credenciais.');
        }
    }
}
