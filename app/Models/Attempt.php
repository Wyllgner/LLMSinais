<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    protected $fillable = [
        'sessao_uuid', 'exercise_id', 'codigo', 'passou',
        'tipo_erro', 'stderr_bruto', 'erro_simplificado',
        'resultado_testes', 'nivel_dica',
    ];

    protected $casts = [
        'passou' => 'boolean',
        'resultado_testes' => 'array',
    ];

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function feedbackLevels(): HasMany
    {
        return $this->hasMany(FeedbackLevel::class)->orderBy('nivel');
    }

    public function primeiroTesteFalho(): ?array
    {
        return collect($this->resultado_testes ?? [])
            ->firstWhere('passou', false);
    }
}
