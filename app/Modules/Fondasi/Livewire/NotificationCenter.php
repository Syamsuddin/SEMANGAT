<?php

namespace App\Modules\Fondasi\Livewire;

use App\Modules\Fondasi\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * FON-07 (dasar, in-app saja — keputusan 2026-11-01): pusat notifikasi pengguna; hanya notifikasi miliknya sendiri.
 *
 * @property-read Collection<int, DatabaseNotification> $notifications
 */
#[Layout('layouts.app')]
class NotificationCenter extends Component
{
    /** @return Collection<int, DatabaseNotification> */
    #[Computed]
    public function notifications(): Collection
    {
        return $this->user()->notifications()->limit(50)->get();
    }

    public function open(string $id): mixed
    {
        $notification = $this->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        if (is_string($url) && str_starts_with($url, (string) config('app.url'))) {
            return $this->redirect($url);
        }

        unset($this->notifications);

        return null;
    }

    public function markAllRead(): void
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
        unset($this->notifications);
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
