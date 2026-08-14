<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $fillable = [
        'slug', 'titulo', 'conceito', 'ordem', 'conteudo', 'exemplo', 'exemplo_execucao',
        'sinais', 'glossario', 'enunciado', 'codigo_inicial', 'casos_teste',
    ];

    protected $casts = [
        'casos_teste' => 'array',
        'sinais' => 'array',
        'glossario' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function concluidoPor(string $sessaoUuid): bool
    {
        return $this->attempts()
            ->where('sessao_uuid', $sessaoUuid)
            ->where('passou', true)
            ->exists();
    }
}
