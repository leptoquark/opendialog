<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use App\Http\Resources\TemplateResourceCollection;
use App\Template;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \App\Http\Resources\TemplateResourceCollection|\Illuminate\Http\Response
     */
    public function index()
    {
        return new TemplateResourceCollection(Template::where('active', true)->paginate(50));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \App\Http\Resources\TemplateResource|\Illuminate\Http\Response
     */
    public function show($id)
    {
        return new TemplateResource(Template::findOrFail($id));
    }
}
