<?php

use App\Modules\Fondasi\Enums\Role;

it('FON-06 peran 2FA tanpa 2FA dialihkan ke setup 2FA dari rute apa pun', function (Role $role) {
    $user = userWithRole($role, twoFactor: false);

    foreach (['fondasi.home', 'fondasi.profile', 'integrasi.panel', 'fondasi.employees.index'] as $route) {
        $this->actingAs($user)->get(route($route))->assertRedirect(route('fondasi.account'));
    }

    $this->actingAs($user)->get(route('fondasi.account'))->assertOk()->assertSee('Aktifkan 2FA');
})->with([Role::Pengelola, Role::Clo, Role::AdminTeknis, Role::Auditor])->group('fondasi');

it('FON-06 alur setup 2FA (aktifkan → QR → konfirmasi) dapat dilalui tanpa dialihkan', function () {
    $user = userWithRole(Role::Pengelola, twoFactor: false);

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'))->assertRedirect();

    $this->actingAs($user->refresh())->get(route('fondasi.account'))->assertOk()->assertSee('Pindai kode QR');
    expect($user->two_factor_secret)->not->toBeNull();
})->group('fondasi');

it('FON-06 peran 2FA dengan 2FA terkonfirmasi dapat mengakses rute', function () {
    $this->actingAs(userWithRole(Role::Pengelola))->get(route('integrasi.panel'))->assertOk();
})->group('fondasi');

it('FON-06 peran tanpa kewajiban 2FA tidak dialihkan', function (Role $role) {
    $this->actingAs(userWithRole($role, twoFactor: false))->get(route('fondasi.home'))->assertOk();
})->with([Role::Asn, Role::Atasan, Role::KoordinatorOpd, Role::Fasilitator, Role::DewanPengarah])->group('fondasi');
