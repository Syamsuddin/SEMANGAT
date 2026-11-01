<?php

use App\Modules\Fondasi\Livewire\NotificationCenter;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Notifications\CertificateRejected;
use App\Modules\JpRiwayat\Notifications\CertificateVerified;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('FON-07 pusat notifikasi in-app hanya memuat notifikasi milik pengguna; jumlah belum dibaca tampil di header', function () {
    $user = employeeUser();
    $other = employeeUser();
    $user->notify(new CertificateVerified(JpRecord::factory()->verified()->create(['title' => 'Diklat Milik Saya', 'jp' => 8])));
    $other->notify(new CertificateRejected(JpRecord::factory()->rejected('Buram')->create(['title' => 'Diklat Orang Lain'])));

    Livewire::actingAs($user)->test(NotificationCenter::class)
        ->assertSee('Diklat Milik Saya')
        ->assertSee('Belum dibaca')
        ->assertDontSee('Diklat Orang Lain');

    $this->actingAs($user)->get(route('fondasi.notifications.index'))->assertOk()->assertSee('Notifikasi, 1 belum dibaca');
})->group('fondasi');

it('FON-07 membuka notifikasi menandainya dibaca dan menuju tautannya; "Tandai semua dibaca"', function () {
    $user = employeeUser();
    $user->notify(new CertificateVerified(JpRecord::factory()->verified()->create()));
    $user->notify(new CertificateVerified(JpRecord::factory()->verified()->create()));
    $first = $user->notifications()->first();

    Livewire::actingAs($user)->test(NotificationCenter::class)
        ->call('open', $first->id)
        ->assertRedirect(route('jp.records.mine'));
    expect($first->refresh()->read_at)->not->toBeNull()->and($user->unreadNotifications()->count())->toBe(1);

    Livewire::actingAs($user)->test(NotificationCenter::class)->call('markAllRead')->assertDispatched('toast');
    expect($user->unreadNotifications()->count())->toBe(0);
})->group('fondasi');

it('FON-07 notifikasi pengguna lain tidak dapat dibuka', function () {
    $other = employeeUser();
    $other->notify(new CertificateVerified(JpRecord::factory()->verified()->create()));

    expect(fn () => Livewire::actingAs(employeeUser())->test(NotificationCenter::class)->call('open', $other->notifications()->first()->id))
        ->toThrow(ModelNotFoundException::class);
    expect($other->unreadNotifications()->count())->toBe(1);
})->group('fondasi');
