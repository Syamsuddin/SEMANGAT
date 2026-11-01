<?php

namespace App\Modules\Fondasi\Actions;

use App\Modules\Fondasi\Models\User;

/**
 * FON-07: tandai notifikasi in-app milik pengguna sebagai dibaca. Tautan notifikasi hanya diikuti bila menuju aplikasi
 * ini sendiri — path relatif, atau URL absolut dengan skema, host, dan port yang sama dengan `app.url`.
 */
class ReadNotifications
{
    /** Tandai satu notifikasi dibaca; kembalikan tautan tujuannya bila aman diikuti. */
    public function open(User $user, string $id): ?string
    {
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        return is_string($url) && $this->isInternal($url) ? $url : null;
    }

    public function markAll(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function isInternal(string $url): bool
    {
        if (str_contains($url, '\\')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        $target = parse_url($url);
        $app = parse_url((string) config('app.url'));

        if (! is_array($target) || ! is_array($app) || ! isset($target['scheme'], $target['host'], $app['scheme'], $app['host'])) {
            return false;
        }

        return $this->origin($target) === $this->origin($app);
    }

    /**
     * @param  array{scheme: string, host: string, port?: int}  $parts
     * @return array{0: string, 1: string, 2: int|null}
     */
    private function origin(array $parts): array
    {
        $scheme = strtolower($parts['scheme']);

        return [$scheme, strtolower($parts['host']), $parts['port'] ?? match ($scheme) {
            'https' => 443,
            'http' => 80,
            default => null,
        }];
    }
}
