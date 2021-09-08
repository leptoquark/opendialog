<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property array $data
 * @property boolean $active
 */
class Template extends VariableConnectionModel
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'data', 'active'];

    protected $casts = [
        'data' => 'array',
        'active' => 'boolean'
    ];
}
