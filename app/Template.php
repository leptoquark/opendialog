<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property array $data
 */
class Template extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'data'];

    protected $casts = [
        'data' => 'array'
    ];
}
