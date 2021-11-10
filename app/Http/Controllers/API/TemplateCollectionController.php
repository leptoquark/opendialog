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
        $templateCollections = $this->getTemplateCollectionQuery()
            ->where('active', 1)
            ->get();

        return new TemplateCollectionCollection($templateCollections);
    }

    public function handle($templateCollectionId)
    {
        $templateCollection = $this->getTemplateCollectionQuery()
            ->where('id', $templateCollectionId)
            ->first();

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
}
