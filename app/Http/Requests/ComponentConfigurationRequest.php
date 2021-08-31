<?php

namespace App\Http\Requests;

use App\Rules\ComponentConfigurationRule;
use App\Rules\ComponentRegistrationRule;
use App\Rules\PublicUrlRule;
use App\Rules\ScenarioExists;
use App\Rules\UrlSchemeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

/**
 * @property $name string
 * @property $scenario_id string
 * @property $component_id string
 * @property $configuration array
 */
class ComponentConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'scenario_id' => [
                'bail',
                Rule::requiredIf($this->method() == 'POST' || !is_null($this->name)),
                'string',
                'filled',
                new ScenarioExists,
            ],
            'name' => [
                'bail',
                Rule::requiredIf($this->method() == 'POST' || !is_null($this->scenario_id)),
                'string',
                'filled',
                Rule::unique('component_configurations')->where(function ($query) {
                    if ($this->route('component_configuration')) {
                        $query->where('id', '!=', $this->route('component_configuration')->id);
                    }

                    return $query->where('scenario_id', $this->scenario_id);
                }),
            ],
            'component_id' => [
                'bail',
                Rule::requiredIf($this->method() == 'POST' || !is_null($this->configuration)),
                'string',
                'filled',
                new ComponentRegistrationRule
            ],
            'configuration' => [
                'bail',
                Rule::requiredIf($this->method() == 'POST' || !is_null($this->component_id)),
                'array',
                new ComponentConfigurationRule($this->component_id ?? '')
            ],
            'active' => [
                'bail',
                'boolean',
            ]
        ];

        // Only set the URL validation rules if we are not in debug mode to allow local URLs during local development
        if (config('app.env') !== 'local') {
            $rules['configuration.app_url'] = [
                'active_url',
                new PublicUrlRule,
                new UrlSchemeRule
            ];

            $rules['configuration.webhook_url'] = [
                'active_url',
                new PublicUrlRule,
                new UrlSchemeRule
            ];
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        if ($this->route('component_configuration') && $this->name) {
            /** @var ComponentConfiguration $configuration */
            $configuration = $this->route('component_configuration');

            $this->merge([
                'scenario_id' => $configuration->scenario_id,
            ]);
        }
    }
}
