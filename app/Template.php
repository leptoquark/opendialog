<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property array $data
 * @property boolean $active
 * @property string $platform_id
 * @property TemplateCollection $templateCollection
 */
class Template extends VariableConnectionModel
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'data', 'active'];

    protected $casts = [
        'data' => 'array',
        'active' => 'boolean'
    ];

    public function templateCollection(): BelongsTo
    {
        return $this->belongsTo(TemplateCollection::class, 'template_collection_id');
    }
}
