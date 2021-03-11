<?php

namespace App\Http\Requests;

use App\Rules\Status;
use Illuminate\Foundation\Http\FormRequest;

class ScenarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'uid' => 'string',
            'odID' => 'string',
            'name' => 'string',
            'description' => 'string',
            'defaultInterpreter' => 'string',
            'behaviours' => 'array',
            'conditions' => 'array',
            'status' => ['string', new Status],
            'conversations' => 'array'
        ];
    }
}
