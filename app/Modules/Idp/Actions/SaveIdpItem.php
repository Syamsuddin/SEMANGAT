<?php

namespace App\Modules\Idp\Actions;

use App\Modules\Fondasi\Models\User;
use App\Modules\Idp\Models\Idp;
use App\Modules\Idp\Models\IdpItem;
use App\Modules\Idp\Services\DraftBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * IDP-02: tambah/ubah butir kompetensi prioritas. Butir ke-4 ditolak. Tautan RHK hanya ke RHK SKP milik pegawai
 * pada periode IDP (NULL = belum tertaut → ditandai IDP-10 saat diajukan).
 */
class SaveIdpItem
{
    public function __construct(private readonly DraftBuilder $drafts) {}

    /** @param  array<string, mixed>  $input */
    public function handle(User $user, Idp $idp, array $input, ?IdpItem $item = null): IdpItem
    {
        Gate::forUser($user)->authorize('update', $idp);
        abort_if($item !== null && $item->idp_id !== $idp->id, 404);

        if ($item === null && $idp->items()->count() >= Idp::MAX_ITEMS) {
            throw ValidationException::withMessages(['items' => __('idp.errors.max_items', ['max' => Idp::MAX_ITEMS])]);
        }

        $workResultIds = array_keys($this->drafts->workResults($idp->employee, $idp->period_year));

        $data = Validator::make($input, [
            'competency_id' => [
                'required', 'integer',
                Rule::exists('cmp_competencies', 'id')->where('is_active', true),
                Rule::unique('pln_idp_items', 'competency_id')->where('idp_id', $idp->id)->ignore($item?->id),
            ],
            'start_level' => ['required', 'integer', 'min:0', 'max:5'],
            'target_level' => ['required', 'integer', 'min:1', 'max:5', 'gt:start_level'],
            'skp_work_result_id' => ['nullable', 'integer', Rule::in($workResultIds)],
            'success_indicator' => ['nullable', 'string', 'max:2000'],
            'mentor_employee_id' => ['nullable', 'integer', Rule::exists('hr_employees', 'id')->where('status', 'active'), Rule::notIn([$idp->employee_id])],
        ], [], __('idp.attributes'))->validate();

        if ($item === null) {
            return $idp->items()->create($data + ['position' => ((int) $idp->items()->max('position')) + 1]);
        }

        $item->fill($data)->save();

        return $item;
    }
}
