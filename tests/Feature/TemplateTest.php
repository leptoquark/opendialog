<?php

namespace Tests\Feature;

use App\Template;
use App\TemplateCollection;
use App\User;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
    }

    public function testView()
    {
        /** @var TemplateCollection $collection */
        $collection = TemplateCollection::factory()->create();

        /** @var Template $template */
        $template = Template::factory()->create([
            'template_collection_id' => $collection->id
        ]);

        $this->get('/admin/api/templates/'.$template->id)
            ->assertStatus(302);

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/templates/'.$template->id)
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => $template->name,
                'description' => $template->description,
                'data' => $template->data,
                'platform_id' => $template->platform_id
            ]);
    }

    public function testViewAll()
    {
        /** @var TemplateCollection $collection */
        $collection = TemplateCollection::factory()->create();

        for ($i = 0; $i < 51; $i++) {
            Template::factory()->create([
                'template_collection_id' => $collection->id
            ]);
        }

        Template::factory()->create([
            'active' => false,
            'template_collection_id' => $collection->id
        ]);

        $templates = Template::all();

        $this->get('/admin/api/templates/')
            ->assertStatus(302);

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/templates/?page=1')
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    $templates[0]->toArray(),
                    $templates[1]->toArray(),
                    $templates[2]->toArray(),
                ],
            ])
            ->getData();

        $this->assertEquals(50, count($response->data));

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/templates?page=2')
            ->assertStatus(200)
            ->getData();

        $this->assertEquals(1, count($response->data));
    }
}
