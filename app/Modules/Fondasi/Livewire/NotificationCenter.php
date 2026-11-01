<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Actions\ReadNotifications;
use App\Modules\Fondasi\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * FON-07 (dasar, in-app saja — keputusan 2026-11-01): pusat notifikasi pengguna; hanya notifikasi miliknya sendiri.
 * Dimuat per 50; "Tandai semua dibaca" mengikuti jumlah belum dibaca seluruhnya, bukan hanya yang termuat.
 *
 * @property-read Collection<int, DatabaseNotification> $notifications
 * @property-read int $unreadCount
 * @property-read bool $hasMore
 */
#[Layout('layouts.app')]
class NotificationCenter extends Component
{
    public const PER_PAGE = 50;

    public int $limit = self::PER_PAGE;

    /** @return Collection<int, DatabaseNotification> */
    #[Computed]
    public function notifications(): Collection
    {
        return $this->user()->notifications()->limit($this->limit)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return $this->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->user()->notifications()->count() > $this->limit;
    }

    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
        unset($this->notifications, $this->hasMore);
    }

    public function open(string $id, ReadNotifications $read): mixed
    {
        $url = $read->open($this->user(), $id);

        if ($url !== null) {
            return $this->redirect($url);
        }

        unset($this->notifications, $this->unreadCount);

        return null;
    }

    public function markAllRead(ReadNotifications $read): void
    {
        $read->markAll($this->user());
        unset($this->notifications, $this->unreadCount);
        $this->dispatch('toast', type: 'success', message: __('fondasi.notifications.all_read'));
    }

    public function render(): View
    {
        return view('fondasi::livewire.notification-center')->title(__('fondasi.notifications.title'));
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
