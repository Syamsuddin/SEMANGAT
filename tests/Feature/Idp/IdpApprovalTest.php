<?php

use App\Modules\Fondasi\Enums\Role;
use App\Modules\Idp\Actions\ApproveIdpBatch;
use App\Modules\Idp\Enums\IdpStatus;
use App\Modules\Idp\Livewire\IdpEditor;
use App\Modules\Idp\Livewire\TeamApprovalList;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Notifications\IdpApproved;
use App\Modules\Idp\Notifications\IdpReturned;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Notification::fake();
    $this->atasan = userWithRole(Role::Atasan);
    $this->team = Employee::factory()->count(4)->supervisedBy($this->atasan->employee)->create()
        ->push(Employee::query()->where('supervisor_id', $this->atasan->employee_id)->firstOrFail());
    $this->idps = $this->team->map(fn (Employee $e) => idpWithItems($e, 2, 'submitted'));
    $this->users = $this->team->map(fn (Employee $e) => employeeUser($e));
});

it('IDP-04 atasan memilih 5 IDP submitted lalu "Setujui" dengan satu catatan → kelimanya approved + notifikasi', function () {
    $component = Livewire::actingAs($this->atasan)->test(TeamApprovalList::class);
    foreach ($this->team as $e) {
        $component->assertSee($e->name);
    }

    $component->call('toggleAll')
        ->assertSet('selected', $this->idps->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->set('note', 'Fokus pada penerapan di pekerjaan.')
        ->call('approveSelected')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSee('Tidak ada IDP yang menunggu persetujuan.');

    Idp::query()->get()->each(fn (Idp $idp) => expect($idp)
        ->status->toBe(IdpStatus::Approved)
        ->supervisor_note->toBe('Fokus pada penerapan di pekerjaan.')
        ->approved_at->not->toBeNull());
    $this->users->each(fn ($u) => Notification::assertSentTo($u, IdpApproved::class));
})->group('idp');

it('IDP-04 halaman persetujuan tim dapat dibuka (tata letak ponsel memakai kartu & checkbox)', function () {
    $this->actingAs($this->atasan)->get(route('idp.team.index'))
        ->assertOk()->assertSee('Pilih semua')->assertSee('type="checkbox"', false)->assertSee('Setujui');
})->group('idp');

it('IDP-04 batch berisi IDP bukan bawahan langsung ditolak seluruhnya', function () {
    $foreign = idpWithItems(Employee::factory()->create(), 2, 'submitted');

    expect(fn () => app(ApproveIdpBatch::class)->handle($this->atasan, [$this->idps[0]->id, $foreign->id]))
        ->toThrow(NotFoundHttpException::class);
    expect($this->idps[0]->refresh()->status)->toBe(IdpStatus::Submitted);
})->group('idp');

it('IDP-04 asn tidak dapat menyetujui IDP sendiri', function () {
    $own = idpWithItems($this->atasan->employee, 2, 'submitted');
    $own->update(['supervisor_id' => $this->atasan->employee_id]);

    expect(fn () => app(ApproveIdpBatch::class)->handle($this->atasan, [$own->id]))->toThrow(AuthorizationException::class);
})->group('idp');

it('IDP-04 atasan mengembalikan IDP dengan catatan wajib → draf + notifikasi', function () {
    $idp = $this->idps[0];

    Livewire::actingAs($this->atasan)->test(TeamApprovalList::class)
        ->call('returnIdp', $idp->id)->assertHasErrors('note')
        ->set("returnNotes.{$idp->id}", 'Tautkan butir kedua ke RHK.')
        ->call('returnIdp', $idp->id)->assertHasNoErrors();

    expect($idp->refresh())->status->toBe(IdpStatus::Draft)->supervisor_note->toBe('Tautkan butir kedua ke RHK.');
    Notification::assertSentTo($this->users[0], IdpReturned::class);
    Livewire::actingAs($this->users[0])->test(IdpEditor::class)->assertSee('Tautkan butir kedua ke RHK.');
})->group('idp');

it('IDP-04 notifikasi tersimpan di kanal database', function () {
    Notification::swap(new ChannelManager(app()));
    app(ApproveIdpBatch::class)->handle($this->atasan, [$this->idps[0]->id], 'Catatan');

    expect($this->users[0]->notifications()->count())->toBe(1)
        ->and($this->users[0]->notifications()->first()->data['idp_id'])->toBe($this->idps[0]->id);
})->group('idp');
