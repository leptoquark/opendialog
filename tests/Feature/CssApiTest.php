<?php

namespace Tests\Feature;

use App\Http\Controllers\API\ExternalCssController;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CssApiTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function testGetExternalCssNotAuthed()
    {
        $data = ['path' => 'http://path-to.css'];
        $this->json("GET", '/admin/api/external-css', $data)
            ->assertStatus(401);
    }

    public function testGetExternalCssNotFound()
    {
        $data = ['path' => 'http://path-to.css'];
        $this->actingAs($this->user, 'api')
            ->json("GET", '/admin/api/external-css', $data)
            ->assertStatus(400);
    }

    public function testGetExternalCssNoPath()
    {
        $this->actingAs($this->user, 'api')
            ->json("GET", '/admin/api/external-css')
            ->assertStatus(404);
    }

    public function testGetExternalCssWrongType()
    {
        $this->app->bind(ExternalCssController::class, function () {
            $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'not-css'], "")
            ]);

            $handler = HandlerStack::create($mock);

            return new ExternalCssController(new Client(['handler' => $handler]));
        });

        $data = ['path' => 'http://path-to.css'];
        $this->actingAs($this->user, 'api')
            ->json("GET", '/admin/api/external-css', $data)
            ->assertStatus(400);
    }

    public function testGetExternalCssSuccess()
    {
        $css = "{css}";
        $this->app->bind(ExternalCssController::class, function () use ($css) {
            $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/css'], $css)
            ]);

            $handler = HandlerStack::create($mock);

            return new ExternalCssController(new Client(['handler' => $handler]));
        });

        $data = ['path' => 'http://path-to.css'];
        $this->actingAs($this->user, 'api')
            ->json("GET", '/admin/api/external-css', $data)
            ->assertStatus(200)
            ->assertSeeText($css);
    }
}
