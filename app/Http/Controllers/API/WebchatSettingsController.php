<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WebchatSettingsResourceCollection;
use OpenDialogAi\Webchat\WebchatSetting;

class WebchatSettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return WebchatSettingsResourceCollection
     */
    public function index(): WebchatSettingsResourceCollection
    {
        return new WebchatSettingsResourceCollection(
            WebchatSetting::all()
        );
    }
}
