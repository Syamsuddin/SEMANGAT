<?php

namespace App\Modules\Kompetensi\Actions;

use App\Modules\Fondasi\Enums\AuditAction;
use App\Modules\Fondasi\Models\User;
use App\Modules\Integrasi\Models\Employee;
use App\Modules\Kompetensi\Data\ImportReport;
use App\Modules\Kompetensi\Enums\AssessmentMethod;
use App\Modules\Kompetensi\Enums\CompetencyType;
use App\Modules\Kompetensi\Events\AssessmentRecorded;
use App\Modules\Kompetensi\Jobs\RecalculateGapsJob;
use App\Modules\Kompetensi\Models\Assessment;
use App\Modules\Kompetensi\Models\Competency;
use App\Modules\Kompetensi\Models\JobStandard;
use App\Modules\Kompetensi\Models\JobStandardItem;
use App\Modules\Kompetensi\Services\CompetencyFileReader;
use App\Support\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * KOM-01/02/03: impor berkas kamus kompetensi, SKJ, atau hasil uji resmi (keputusan pemilik produk 2026-10-30).
 * Pola INT-03: satu baris tidak valid → seluruh berkas ditolak, laporan per baris; impor ulang idempoten.
 */
class ImportCompetencyFile
{
    public const DICTIONARY = 'kamus';

    public const STANDARD = 'skj';

    public const ASSESSMENT = 'hasil-uji';

    public const SOURCE_STANDARD = 'skj_file';

    /** @var array<string, list<string>> kolom templat per jenis berkas */
    public const COLUMNS = [
        self::DICTIONARY => ['kode', 'nama', 'jenis', 'deskripsi', 'instansi_pembina', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5', 'aktif'],
        self::STANDARD => ['kode_jabatan', 'nama_skj', 'berlaku_mulai', 'kode_kompetensi', 'level'],
        self::ASSESSMENT => ['nip', 'kode_kompetensi', 'level', 'metode', 'lembaga', 'tanggal_uji', 'berlaku_sampai', 'catatan'],
    ];

    /** @var array<string, list<string>> kolom yang wajib ada di baris judul */
    private const REQUIRED_HEADERS = [
        self::DICTIONARY => ['kode', 'nama', 'jenis'],
        self::STANDARD => ['kode_jabatan', 'nama_skj', 'berlaku_mulai', 'kode_kompetensi', 'level'],
        self::ASSESSMENT => ['nip', 'kode_kompetensi', 'level', 'metode', 'tanggal_uji'],
    ];

    private const TYPES = [
        'manajerial' => CompetencyType::Managerial, 'managerial' => CompetencyType::Managerial,
        'sosiokultural' => CompetencyType::Sociocultural, 'sociocultural' => CompetencyType::Sociocultural,
        'teknis' => CompetencyType::Technical, 'technical' => CompetencyType::Technical,
    ];

    private const METHODS = [
        'assessment_center' => AssessmentMethod::AssessmentCenter,
        'uji_kompetensi_jf' => AssessmentMethod::JfCompetencyTest, 'jf_competency_test' => AssessmentMethod::JfCompetencyTest,
        'disimpulkan_skp' => AssessmentMethod::InferredSkp, 'inferred_skp' => AssessmentMethod::InferredSkp,
    ];

    public function __construct(private readonly CompetencyFileReader $reader, private readonly AuditLogger $audit) {}

    public function __invoke(string $kind, string $path, User $by): ImportReport
    {
        if (! isset(self::COLUMNS[$kind])) {
            throw new InvalidArgumentException(__('kompetensi.import.unknown_kind'));
        }

        try {
            $rows = $this->reader->rows($path);
        } catch (InvalidArgumentException $e) {
            return ImportReport::failed($e->getMessage());
        } catch (Throwable) {
            return ImportReport::failed(__('kompetensi.import.unreadable'));
        }
        if ($rows === []) {
            return ImportReport::failed(__('kompetensi.import.empty'));
        }
        $missing = array_diff(self::REQUIRED_HEADERS[$kind], array_keys(reset($rows)));
        if ($missing !== []) {
            return ImportReport::failed(__('kompetensi.import.headers', ['columns' => implode(', ', $missing)]));
        }
        $template = array_fill_keys(self::COLUMNS[$kind], null);
        $rows = array_map(fn (array $row) => $row + $template, $rows);

        return match ($kind) {
            self::DICTIONARY => $this->importDictionary($rows),
            self::STANDARD => $this->importStandards($rows),
            default => $this->importAssessments($rows, $by),
        };
    }

    /** @param array<int, array<string, string|null>> $rows */
    private function importDictionary(array $rows): ImportReport
    {
        $errors = [];
        $seen = [];
        foreach ($rows as $n => $row) {
            $e = $this->required($row, ['kode', 'nama', 'jenis']);
            $e = [...$e, ...$this->maxLength($row, ['kode' => 40, 'nama' => 200, 'instansi_pembina' => 150])];
            if ($row['jenis'] !== null && ! isset(self::TYPES[mb_strtolower($row['jenis'])])) {
                $e[] = __('kompetensi.import.type');
            }
            if ($row['aktif'] !== null && ! in_array(mb_strtolower($row['aktif']), ['ya', 'tidak'], true)) {
                $e[] = __('kompetensi.import.active');
            }
            if ($row['kode'] !== null && isset($seen[$row['kode']])) {
                $e[] = __('kompetensi.import.duplicate_row', ['row' => $seen[$row['kode']]]);
            }
            $seen[$row['kode'] ?? ''] ??= $n;
            if ($e !== []) {
                $errors[$n] = $e;
            }
        }
        if ($errors !== []) {
            return ImportReport::rejected($errors);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $descriptors = [];
                foreach (range(1, 5) as $level) {
                    if (($text = $row["level_{$level}"] ?? null) !== null) {
                        $descriptors[(string) $level] = $text;
                    }
                }
                Competency::query()->updateOrCreate(['code' => $row['kode']], [
                    'name' => $row['nama'],
                    'type' => self::TYPES[mb_strtolower((string) $row['jenis'])],
                    'description' => $row['deskripsi'] ?? null,
                    'steward' => $row['instansi_pembina'] ?? null,
                    'level_descriptors' => $descriptors === [] ? null : $descriptors,
                    'is_active' => mb_strtolower($row['aktif'] ?? 'ya') === 'ya',
                ]);
            }
        });

        return new ImportReport(count($rows));
    }

    /** @param array<int, array<string, string|null>> $rows */
    private function importStandards(array $rows): ImportReport
    {
        $competencies = Competency::query()->pluck('id', 'code')->all();
        $errors = [];
        $seen = [];
        foreach ($rows as $n => $row) {
            $e = $this->required($row, ['kode_jabatan', 'nama_skj', 'berlaku_mulai', 'kode_kompetensi', 'level']);
            $e = [...$e, ...$this->maxLength($row, ['kode_jabatan' => 40, 'nama_skj' => 200])];
            if ($row['berlaku_mulai'] !== null && $this->date($row['berlaku_mulai']) === null) {
                $e[] = __('kompetensi.import.date', ['field' => 'berlaku_mulai']);
            }
            if ($row['kode_kompetensi'] !== null && ! isset($competencies[$row['kode_kompetensi']])) {
                $e[] = __('kompetensi.import.competency_unknown');
            }
            if ($row['level'] !== null && ! $this->validLevel($row['level'])) {
                $e[] = __('kompetensi.import.level');
            }
            $key = implode('|', [$row['kode_jabatan'], $row['berlaku_mulai'], $row['kode_kompetensi']]);
            if (isset($seen[$key])) {
                $e[] = __('kompetensi.import.duplicate_row', ['row' => $seen[$key]]);
            }
            $seen[$key] ??= $n;
            if ($e !== []) {
                $errors[$n] = $e;
            }
        }
        if ($errors !== []) {
            return ImportReport::rejected($errors);
        }

        $groups = [];
        foreach ($rows as $row) {
            $effective = (string) $this->date((string) $row['berlaku_mulai']);
            $groups[$row['kode_jabatan'].'@'.$effective][] = $row + ['_effective' => $effective];
        }

        DB::transaction(function () use ($groups, $competencies) {
            foreach ($groups as $externalId => $groupRows) {
                $standard = JobStandard::query()->updateOrCreate(
                    ['source' => self::SOURCE_STANDARD, 'external_id' => $externalId],
                    [
                        'position_code' => $groupRows[0]['kode_jabatan'],
                        'name' => $groupRows[0]['nama_skj'],
                        'effective_on' => $groupRows[0]['_effective'],
                        'synced_at' => now(),
                    ],
                );
                $levels = [];
                foreach ($groupRows as $row) {
                    $levels[(int) $competencies[$row['kode_kompetensi']]] = (int) $row['level'];
                }
                JobStandardItem::query()->where('job_standard_id', $standard->id)->whereNotIn('competency_id', array_keys($levels))->delete();
                foreach ($levels as $competencyId => $level) {
                    JobStandardItem::query()->updateOrCreate(
                        ['job_standard_id' => $standard->id, 'competency_id' => $competencyId],
                        ['required_level' => $level],
                    );
                }
            }
        });

        RecalculateGapsJob::dispatch();

        return new ImportReport(count($rows));
    }

    /** @param array<int, array<string, string|null>> $rows */
    private function importAssessments(array $rows, User $by): ImportReport
    {
        $competencies = Competency::query()->pluck('id', 'code')->all();
        $nips = array_values(array_unique(array_filter(array_column($rows, 'nip'))));
        $employees = Employee::query()->whereIn('nip', $nips)->pluck('id', 'nip')->all();
        $errors = [];
        $seen = [];
        foreach ($rows as $n => $row) {
            $e = $this->required($row, ['nip', 'kode_kompetensi', 'level', 'metode', 'tanggal_uji']);
            $e = [...$e, ...$this->maxLength($row, ['lembaga' => 150])];
            if ($row['nip'] !== null && ! isset($employees[$row['nip']])) {
                $e[] = __('kompetensi.import.employee_unknown');
            }
            if ($row['kode_kompetensi'] !== null && ! isset($competencies[$row['kode_kompetensi']])) {
                $e[] = __('kompetensi.import.competency_unknown');
            }
            if ($row['level'] !== null && ! $this->validLevel($row['level'])) {
                $e[] = __('kompetensi.import.level');
            }
            if ($row['metode'] !== null && ! isset(self::METHODS[mb_strtolower($row['metode'])])) {
                $e[] = __('kompetensi.import.method');
            }
            $assessed = $row['tanggal_uji'] !== null ? $this->date($row['tanggal_uji']) : null;
            if ($row['tanggal_uji'] !== null && $assessed === null) {
                $e[] = __('kompetensi.import.date', ['field' => 'tanggal_uji']);
            }
            if ($row['berlaku_sampai'] !== null) {
                $until = $this->date($row['berlaku_sampai']);
                if ($until === null) {
                    $e[] = __('kompetensi.import.date', ['field' => 'berlaku_sampai']);
                } elseif ($assessed !== null && $until < $assessed) {
                    $e[] = __('kompetensi.import.valid_until_before');
                }
            }
            $key = implode('|', [$row['nip'], $row['kode_kompetensi'], mb_strtolower((string) $row['metode']), $assessed]);
            if (isset($seen[$key])) {
                $e[] = __('kompetensi.import.duplicate_row', ['row' => $seen[$key]]);
            }
            $seen[$key] ??= $n;
            if ($e !== []) {
                $errors[$n] = $e;
            }
        }
        if ($errors !== []) {
            return ImportReport::rejected($errors);
        }

        $touched = DB::transaction(function () use ($rows, $employees, $competencies, $by) {
            $touched = [];
            foreach ($rows as $row) {
                $method = self::METHODS[mb_strtolower((string) $row['metode'])];
                $assessment = Assessment::query()->firstOrNew([
                    'employee_id' => $employees[$row['nip']],
                    'competency_id' => $competencies[$row['kode_kompetensi']],
                    'method' => $method->value,
                    'assessed_on' => $this->date((string) $row['tanggal_uji']),
                ]);
                $isNew = ! $assessment->exists;
                $assessment->fill([
                    'level' => (int) $row['level'],
                    'confidence' => $method->confidence(),
                    'institution' => $row['lembaga'] ?? null,
                    'valid_until' => $row['berlaku_sampai'] !== null ? $this->date($row['berlaku_sampai']) : null,
                    'notes' => $row['catatan'] ?? null,
                ])->save();
                $this->audit->log($isNew ? AuditAction::Create : AuditAction::Update, $assessment,
                    changes: ['level' => (int) $row['level'], 'method' => $method->value, 'via' => 'import'], userId: $by->id);
                $touched[$assessment->employee_id] = true;
            }

            return array_keys($touched);
        });

        foreach ($touched as $employeeId) {
            AssessmentRecorded::dispatch($employeeId);
        }

        return new ImportReport(count($rows));
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function required(array $row, array $columns): array
    {
        $missing = array_values(array_filter($columns, fn ($c) => ($row[$c] ?? null) === null));

        return $missing === [] ? [] : [__('kompetensi.import.required', ['fields' => implode(', ', $missing)])];
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, int>  $limits
     * @return list<string>
     */
    private function maxLength(array $row, array $limits): array
    {
        $errors = [];
        foreach ($limits as $column => $max) {
            if (($row[$column] ?? null) !== null && mb_strlen((string) $row[$column]) > $max) {
                $errors[] = __('kompetensi.import.too_long', ['field' => $column, 'max' => $max]);
            }
        }

        return $errors;
    }

    private function validLevel(string $value): bool
    {
        return ctype_digit($value) && (int) $value >= 1 && (int) $value <= 5;
    }

    /** Tanggal ISO `YYYY-MM-DD` → string ternormalisasi, atau null bila tidak valid. */
    private function date(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== null && $date->format('Y-m-d') === $value ? $value : null;
    }
}
