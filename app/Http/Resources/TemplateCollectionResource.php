<?php

namespace App\Http\Resources;

use App\Template;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

class TemplateCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $root = parent::toArray($request);

        // Strip any array keys from the description data
        $root['description'] = array_values($this->resource->description);

        $platforms = $this->resource->templates->transform(
            fn (Template $template) => $template->platform_id
        );

        $allPlatforms = $this->getRegisteredPlatforms();

        $root['platforms'] = $platforms->toArray();
        $root['all'] = count(array_diff($allPlatforms->toArray(), $platforms->toArray())) === 0;

        return $root;
    }

    /**
     * @return mixed
     */
    protected function getRegisteredPlatforms()
    {
        return ComponentConfiguration::where('component_id', 'like', 'platform.%')
            ->get()
            ->unique('component_id')
            ->map(fn($config) => $config->component_id);
    }
}
