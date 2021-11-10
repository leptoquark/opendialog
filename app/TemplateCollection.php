<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property array $description
 * @property array $preview
 * @property boolean $active
 * @property string $name
 * @property Template[] $templates
 * @property Template[] $templatesNoData
 */
class TemplateCollection extends Model
{
    use HasFactory;

    protected $casts = [
        'description' => 'array',
        'preview' => 'array',
        'active' => 'boolean'
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }
}
