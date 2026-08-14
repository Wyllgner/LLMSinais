<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackLevel extends Model
{
    protected $fillable = [
        'attempt_id', 'nivel', 'texto', 'segmentos',
        'tokens_entrada', 'tokens_saida', 'tokens_raciocinio', 'chamadas',
        'sinalizabilidade', 'violacoes',
    ];

    protected $casts = [
        'segmentos' => 'array',
        'violacoes' => 'array',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }
}
