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
        /** @var ComponentConfiguration $webchatComponent */
        $webchatComponent = factory(ComponentConfiguration::class)->create([
            'component_id' => 'platform.core.webchat'
        ]);

        /** @var ComponentConfiguration $alexaComponent */
        $alexaComponent = factory(ComponentConfiguration::class)->create([
            'component_id' => 'platform.core.alexa'
        ]);

        /** @var ComponentConfiguration $facebookComponent */
        $facebookComponent = factory(ComponentConfiguration::class)->create([
            'component_id' => 'platform.core.facebook'
        ]);

        /** @var ComponentConfiguration $whatsappComponent */
        $whatsappComponent = factory(ComponentConfiguration::class)->create([
            'component_id' => 'platform.core.whatsapp'
        ]);

        /** @var TemplateCollection $templateCollection1 */
        $templateCollection1 = TemplateCollection::factory()->create([
            'name' => 'FAQ',
            'preview' => [
                'url' => 'https://od-demos.cloud.opendialog.ai/vendor/webchat/js/opendialog-bot.js',
                'selected_scenario' => '0x3bfa9f',
            ]
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => $webchatComponent->component_id
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => $alexaComponent->component_id
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => $facebookComponent->component_id
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection1->id,
            'platform_id' => $whatsappComponent->component_id
        ]);

        /** @var TemplateCollection $templateCollection2 */
        $templateCollection2 = TemplateCollection::factory()->create([
            'name' => 'Product Chooser',
            'preview' => [
                'url' => 'https://od-demos.cloud.opendialog.ai/vendor/webchat/js/opendialog-bot.js',
                'selected_scenario' => '0x3bf93c',
            ]
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection2->id,
            'platform_id' => $webchatComponent->component_id
        ]);

        Template::factory()->create([
            'template_collection_id' => $templateCollection2->id,
            'platform_id' => $alexaComponent->component_id
        ]);
    }
}
