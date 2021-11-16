<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property array $description
 * @property array $preview
 * @property boolean $active
 * @property string $name
 * @property Template[] $templates
 * @property Template[] $templatesNoData
 * @property boolean $default
 */
class TemplateCollection extends VariableConnectionModel
{
    use HasFactory;

    protected $casts = [
        'description' => 'array',
        'preview' => 'array',
        'active' => 'boolean',
        'default' => 'boolean'
    ];

    protected $fillable = [
        'name',
        'description',
        'active',
        'default'
    ];

    /**
     * Ensure that we delete the child templates when a template collection is deleted
     */
    protected static function booted()
    {
        static::deleting(function (TemplateCollection $templateCollection) {
            $templateCollection->templates()->delete();
        });
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }
}
