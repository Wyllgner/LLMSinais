<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicaoTraducao extends Model
{
    protected $table = 'medicoes_traducao';

    protected $fillable = ['sessao_uuid', 'origem', 'texto', 'glosa', 'sinais', 'soletrados'];

    protected $casts = ['soletrados' => 'array'];
}
