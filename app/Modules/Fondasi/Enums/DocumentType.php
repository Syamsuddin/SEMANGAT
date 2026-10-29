<?php

namespace App\Modules\Fondasi\Enums;

/** out_issued_documents.type (docs/07). */
enum DocumentType: string
{
    case LearningPlan = 'learning_plan';
    case LearningReport = 'learning_report';
    case Certificate = 'certificate';
    case Hcdp = 'hcdp';
    case ForumMinutes = 'forum_minutes';
    case StrategicBrief = 'strategic_brief';
    case CorpuReport = 'corpu_report';

    public function label(): string
    {
        return __('fondasi.document_types.'.$this->value);
    }
}
