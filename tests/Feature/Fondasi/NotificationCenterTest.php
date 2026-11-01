<?php

use App\Modules\Fondasi\Livewire\NotificationCenter;
use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Notifications\IdpApproved;
use App\Modules\Idp\Notifications\IdpReturned;
use App\Modules\Idp\Notifications\IdpSubmitted;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\JpRiwayat\Models\JpRecord;
use App\Modules\JpRiwayat\Notifications\CertificateRejected;
use App\Modules\JpRiwayat\Notifications\CertificateVerified;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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

/** Notifikasi basis data mentah dengan tautan sembarang (mis. tersimpan dari host permintaan yang dipalsukan). */
function rawNotification(User $user, ?string $url, ?Carbon $at = null, bool $read = false): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(), 'type' => CertificateVerified::class,
        'data' => ['message' => 'Notifikasi uji', 'url' => $url],
        'read_at' => $read ? now() : null, 'created_at' => $at ?? now(), 'updated_at' => $at ?? now(),
    ]);
}

it('FON-07 tautan notifikasi hanya diikuti bila asalnya sama dengan aplikasi (skema, host, port) atau path relatif', function (string $url, bool $follows) {
    config(['app.url' => 'https://app.example']);
    $user = employeeUser();
    $notification = rawNotification($user, $url);

    $page = Livewire::actingAs($user)->test(NotificationCenter::class)->call('open', $notification->id);

    $follows ? $page->assertRedirect($url) : $page->assertNoRedirect();
    expect($notification->refresh()->read_at)->not->toBeNull();
})->with([
    'asal sama' => ['https://app.example/jp', true],
    'host huruf besar' => ['https://APP.example/jp', true],
    'port bawaan eksplisit' => ['https://app.example:443/jp', true],
    'path relatif' => ['/idp', true],
    'host mirip berawalan sama' => ['https://app.example.evil.test/jp', false],
    'userinfo menipu' => ['https://app.example@evil.test/jp', false],
    'skema berbeda' => ['http://app.example/jp', false],
    'port berbeda' => ['https://app.example:8443/jp', false],
    'protokol-relatif' => ['//evil.test/jp', false],
    'garis miring terbalik' => ['/\\evil.test/jp', false],
    'javascript' => ['javascript:alert(1)', false],
])->group('fondasi');

it('FON-07 notifikasi lama di luar 50 terbaru tetap dapat dimuat; "Tandai semua dibaca" mengikuti seluruh yang belum dibaca', function () {
    $user = employeeUser();
    $oldest = rawNotification($user, null, now()->subDays(60));
    $oldest->forceFill(['data' => ['message' => 'Notifikasi paling lama', 'url' => null]])->save();
    foreach (range(1, NotificationCenter::PER_PAGE) as $i) {
        rawNotification($user, null, now()->subDays(50 - $i), read: true);
    }

    $page = Livewire::actingAs($user)->test(NotificationCenter::class)
        ->assertSee('Tandai semua dibaca')
        ->assertDontSee('Notifikasi paling lama')
        ->assertSee('Muat notifikasi lebih lama')
        ->call('loadMore')
        ->assertSee('Notifikasi paling lama')
        ->assertDontSee('Muat notifikasi lebih lama');

    $page->call('markAllRead');
    expect($user->unreadNotifications()->count())->toBe(0);
})->group('fondasi');

it('FON-07 notifikasi IDP membawa tautan ke halaman terkait: diajukan → persetujuan tim, disetujui/dikembalikan → IDP saya', function () {
    $supervisor = Employee::factory()->create();
    $employee = Employee::factory()->supervisedBy($supervisor)->create();
    $idp = idpWithItems($employee);
    $user = employeeUser($supervisor);

    $user->notify(new IdpSubmitted($idp));
    Livewire::actingAs($user)->test(NotificationCenter::class)
        ->call('open', $user->notifications()->sole()->id)
        ->assertRedirect(route('idp.team.index'));

    expect((new IdpApproved($idp, null))->toArray($user)['url'])->toBe(route('idp.plans.mine', absolute: false))
        ->and((new IdpReturned($idp, 'Perbaiki'))->toArray($user)['url'])->toBe(route('idp.plans.mine', absolute: false));
})->group('fondasi');
