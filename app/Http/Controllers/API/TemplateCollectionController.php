<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateCollectionCollection;
use App\Http\Resources\TemplateCollectionResource;
use App\TemplateCollection;
use Illuminate\Database\Eloquent\Builder;

class TemplateCollectionController extends Controller
{
    public function all()
    {
        if (config('templates.enabled')) {
            $templateCollections = $this->getTemplateCollectionQuery()
                ->where('active', 1)
                ->paginate(50);
        } else {
            $templateCollections = collect([$this->getDefaultTemplate()]);
        }

        return new TemplateCollectionCollection($templateCollections);
    }

    public function handle($templateCollectionId)
    {
        if (config('templates.enabled')) {
            $templateCollection = $this->getTemplateCollectionQuery()
                ->where('id', $templateCollectionId)
                ->first();
        } else {
            $templateCollection = $this->getDefaultTemplate();
        }

        return new TemplateCollectionResource($templateCollection);
    }

    /**
     * Returns a query builder set up to return template collections with only active child templates
     * with a subset of template fields
     *
     * @return Builder
     */
    private function getTemplateCollectionQuery(): Builder
    {
        return TemplateCollection::with(['templates' => function ($query) {
            $query->where('active', 1)
                ->select(['id', 'name', 'description', 'platform_id', 'active', 'template_collection_id']);
        }]);
    }

    private function getDefaultTemplate()
    {
        $default = TemplateCollection::make([
            'name' => 'Custom',
            'description' => 'Start creating sophisticated conversational applications with the OpenDialog framework,
            from scratch for the platform of your choice.',
            'default' => true,
            'active' => true
        ]);

        return $default;
    }
}
