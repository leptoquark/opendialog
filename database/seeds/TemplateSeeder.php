<?php

namespace Database\Seeders;

use App\Template;
use App\TemplateCollection;
use Illuminate\Database\Seeder;
use OpenDialogAi\Core\Components\Configuration\ComponentConfiguration;

class TemplateSeeder extends Seeder
{
    /**
     * NB - this seeder is used to get the database into an initial state
     */
    public function run()
    {
        $defaultScenario = TemplateCollection::factory()->create(array(
            'name' => 'Custom',
            'preview' => array(
                'url' => 'https://od-kamau.cloud.opendialog.ai/vendor/webchat/js/opendialog-bot.js',
                'selected_scenario' => '0x38c0c7',
                'text' => "Click here to see the preview for this template"
            ),
            'description' => "Start creating sophisticated conversational applications with the OpenDialog framework, from
            scratch for the platform of your choice.",
            'default' => true
        ));

        /** @var TemplateCollection $templateCollection1 */
        $templateCollection1 = TemplateCollection::factory()->create([
            'name' => 'FAQ',
            'preview' => [
                'url' => 'https://od-demos.cloud.opendialog.ai/vendor/webchat/js/opendialog-bot.js',
                'selected_scenario' => '0x3bfa9f',
                'text' => "Click here to see the preview for this template"
            ]
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => 'platform.core.webchat'
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => 'platform.voice.alexa'
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => 'platform.core.facebook'
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => 'platform.core.whatsapp'
        ]);

        /** @var TemplateCollection $templateCollection2 */
        $templateCollection2 = TemplateCollection::factory()->create([
            'name' => 'Product Chooser',
            'preview' => [
                'url' => 'https://od-demos.cloud.opendialog.ai/vendor/webchat/js/opendialog-bot.js',
                'selected_scenario' => '0x3bf93c',
                'text' => "Click here to see the preview for this template"
            ]
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection2->id,
            'platform_id' => 'platform.voice.alexa'
        ]);
    }
}
