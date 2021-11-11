<?php

namespace App\Http\Requests;

use App\Rules\Status;

class ScenarioUpdateRequest extends ScenarioRequest
{
    public function rules()
    {
        return [
            'id' => ['bail', 'string', 'filled'],
            'behaviors' => 'array',
            'conditions' => 'array',
            'status' => ['bail', 'string', new Status],
        ] + parent::rules();
    }
}
