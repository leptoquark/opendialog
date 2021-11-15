<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ScenarioCreateRequest extends ScenarioRequest
{
    public function authorize()
    {
        return parent::authorize();
    }

    public function rules()
    {
        $rules = parent::rules();

        if ($this->query('creation_type', 'default') === 'default') {
            $rules['od_id'][] = 'required';
            $rules['name'][] = 'required';
        }

        $creationType = $this->query('creation_type');
        $rules['creation_type'] = ['nullable', 'string', 'in:default,duplicate,from-template'];
        $rules['object_id'] = [Rule::requiredIf(!is_null($creationType) && $creationType !== 'default')];

        return $rules;
    }
}
