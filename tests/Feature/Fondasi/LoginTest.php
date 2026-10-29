<?php

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Enums\Role;
use App\Modules\Fondasi\Models\AuditLog;
use App\Modules\Fondasi\Services\HomeRouteResolver;
use App\Modules\Integrasi\Models\Employee;
use PragmaRX\Google2FA\Google2FA;

it('FON-01 pegawai aktif masuk dengan NIP + kata sandi ke beranda peran utamanya', function () {
    $user = employeeUser();

    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'kata-sandi-uji-123'])
        ->assertRedirect(route('fondasi.home'));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->last_login_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', AuditAction::Login->value)->where('user_id', $user->id)->count())->toBe(1);

    $this->get(route('fondasi.home'))->assertOk()->assertSee($user->employee->name);
})->group('fondasi');

it('FON-01 pegawai inactive ditolak "Akun tidak aktif"', function () {
    $user = employeeUser(Employee::factory()->inactive()->create());

    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'kata-sandi-uji-123'])
        ->assertSessionHasErrors(['username' => 'Akun tidak aktif.']);

    $this->assertGuest();
})->group('fondasi');

it('FON-01 kata sandi salah tidak membocorkan status akun', function () {
    $user = employeeUser(Employee::factory()->inactive()->create());

    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'salah-sama-sekali'])
        ->assertSessionHasErrors(['username' => 'NIP atau kata sandi salah.']);
})->group('fondasi');

it('FON-01 beranda mengikuti peran utama: pengelola ke panel integrasi, lainnya ke beranda', function (Role $role, string $route) {
    expect(app(HomeRouteResolver::class)->routeName(userWithRole($role)))->toBe($route);
})->with([
    [Role::Pengelola, 'integrasi.panel'],
    [Role::Asn, 'fondasi.home'],
    [Role::Atasan, 'fondasi.home'],
    [Role::KoordinatorOpd, 'fondasi.home'],
    [Role::Auditor, 'fondasi.home'],
])->group('fondasi');

it('FON-01 sesi pegawai yang dinonaktifkan sinkron diakhiri', function () {
    $user = employeeUser();
    $this->actingAs($user);
    $user->forceFill(['is_active' => false])->save();

    $this->get(route('fondasi.home'))->assertRedirect(route('login'));
    $this->assertGuest();
})->group('fondasi');

it('FON-01 halaman masuk tampil berbahasa Indonesia', function () {
    $this->get(route('login'))->assertOk()->assertSee('NIP')->assertSee('Kata sandi');
})->group('fondasi');

it('FON-01/06 peran ber-2FA diarahkan ke tantangan 2FA; login baru tercatat sekali setelah kode benar', function () {
    $user = userWithRole(Role::Pengelola);

    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'kata-sandi-uji-123'])
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
    expect(AuditLog::query()->where('action', AuditAction::Login->value)->count())->toBe(0);

    $code = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP');
    $this->post(route('two-factor.login.store'), ['code' => $code])
        ->assertRedirect(route('integrasi.panel'));

    $this->assertAuthenticatedAs($user);
    expect(AuditLog::query()->where('action', AuditAction::Login->value)->where('user_id', $user->id)->count())->toBe(1);
})->group('fondasi');
