<?php

namespace App\Modules\Fondasi\Database\Factories;

use App\Modules\Fondasi\Enums\DocumentType;
use App\Modules\Fondasi\Models\IssuedDocument;
use App\Modules\Integrasi\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<IssuedDocument> */
class IssuedDocumentFactory extends Factory
{
    protected $model = IssuedDocument::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'type' => DocumentType::LearningPlan,
            'documentable_type' => 'pln_idp',
            'documentable_id' => 1,
            'employee_id' => Employee::factory(),
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'sha256' => hash('sha256', Str::random(32)),
            'issued_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
