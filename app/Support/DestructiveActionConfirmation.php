<?php

namespace App\Support;

class DestructiveActionConfirmation
{
    private const SESSION_KEY = 'destructive_confirm';

    public static function issue(string $action, array $context = []): string
    {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        session([
            self::SESSION_KEY => [
                'action' => $action,
                'code' => $code,
                'context' => $context,
                'expires' => now()->addMinutes(10)->timestamp,
            ],
        ]);

        return $code;
    }

    public static function validate(string $action, string $input, array $context = []): bool
    {
        $stored = session(self::SESSION_KEY);

        if (! is_array($stored) || ($stored['action'] ?? '') !== $action) {
            return false;
        }

        if (now()->timestamp > (int) ($stored['expires'] ?? 0)) {
            return false;
        }

        if (strtoupper(trim($input)) !== ($stored['code'] ?? '')) {
            return false;
        }

        foreach ($context as $key => $value) {
            if ((string) ($stored['context'][$key] ?? '') !== (string) $value) {
                return false;
            }
        }

        session()->forget(self::SESSION_KEY);

        return true;
    }
}
