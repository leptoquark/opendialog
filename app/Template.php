<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property array $data
 */
class Template extends VariableConnectionModel
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'data'];

    protected $casts = [
        'data' => 'array'
    ];
}
