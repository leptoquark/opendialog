<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TemplateCollectionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->collection->transform(function (TemplateCollectionResource $c) {
            return new TemplateCollectionResource($c->resource);
        });

        return parent::toArray($request);
    }
}
