<?php


namespace Tests\Feature;

use App\User;
use Carbon\Carbon;
use OpenDialogAi\Core\Conversation\Behavior;
use OpenDialogAi\Core\Conversation\BehaviorsCollection;
use OpenDialogAi\Core\Conversation\ConditionCollection;
use OpenDialogAi\Core\Conversation\Conversation;
use OpenDialogAi\Core\Conversation\ConversationCollection;
use OpenDialogAi\Core\Conversation\Exceptions\ConversationObjectNotFoundException;
use OpenDialogAi\Core\Conversation\Facades\ConversationDataClient;
use OpenDialogAi\Core\Conversation\Facades\SceneDataClient;
use OpenDialogAi\Core\Conversation\Facades\TransitionDataClient;
use OpenDialogAi\Core\Conversation\Intent;
use OpenDialogAi\Core\Conversation\IntentCollection;
use OpenDialogAi\Core\Conversation\MessageTemplate;
use OpenDialogAi\Core\Conversation\MessageTemplateCollection;
use OpenDialogAi\Core\Conversation\Scenario;
use OpenDialogAi\Core\Conversation\Scene;
use OpenDialogAi\Core\Conversation\SceneCollection;
use OpenDialogAi\Core\Conversation\Transition;
use OpenDialogAi\Core\Conversation\Turn;
use OpenDialogAi\Core\Conversation\TurnCollection;
use OpenDialogAi\PlatformEngine\Components\WebchatPlatform;
use Tests\TestCase;

class UIStateControllerTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }


    public function testFocusedConversationNotFound()
    {
        $this->markTestSkipped(
            'Currently the exception thrown in the ConversationDataClient isnt the correct one'
        );
        ConversationDataClient::shouldReceive('getScenarioWithFocusedConversation')
            ->once()
            ->with('test', false)
            ->andThrow(new ConversationObjectNotFoundException());

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/conversation/test')
            ->assertStatus(404);
    }

    public function testGetFocusedScenario()
    {
        $fakeScenario = new Scenario();
        $fakeScenario->setUid('0x0001');
        $fakeScenario->setName("Example scenario");
        $fakeScenario->setOdId('example_scenario');
        $fakeScenario->setDescription('An example scenario');
        $fakeScenario->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScenario->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));

        $fakeConversation = new Conversation($fakeScenario);
        $fakeConversation->setUid('0x0002');
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setDescription("An new example conversation");
        $fakeConversation->setInterpreter('interpreter.core.nlp');
        $fakeConversation->setBehaviors(new BehaviorsCollection([new Behavior(Behavior::STARTING_BEHAVIOR)]));
        $fakeConversation->setConditions(new ConditionCollection());
        $fakeConversation->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));

        $fakeScenario->setConversations(new ConversationCollection([$fakeConversation]));

        $fakeScene = new Scene($fakeConversation);
        $fakeScene->setUid('0x0003');
        $fakeScene->setName('New Example scene');
        $fakeScene->setOdId('new_example_scene');
        $fakeScene->setDescription("An new example scene");
        $fakeScene->setInterpreter('interpreter.core.nlp');
        $fakeScene->setBehaviors(new BehaviorsCollection([new Behavior(Behavior::OPEN_BEHAVIOR)]));
        $fakeScene->setConditions(new ConditionCollection());
        $fakeScene->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScene->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setScenes(new SceneCollection([$fakeScene]));

        $fakeTurn = new Turn($fakeScene);
        $fakeTurn->setUid('0x0004');
        $fakeTurn->setName('fake turn');
        $fakeTurn->setOdId('fake_scene');
        $fakeTurn->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeTurn->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScene->setTurns(new TurnCollection([$fakeTurn]));

        $fakeIntent = new Intent($fakeTurn);
        $fakeIntent->setUid('0x9998');
        $fakeIntent->setOdId('fake_intent_1');
        $fakeIntent->setName('fake intent 1');
        $fakeIntent->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent]));

        $fakeIntent2 = new Intent($fakeTurn);
        $fakeIntent2->setUid('0x9999');
        $fakeIntent2->setOdId('fake_intent_2');
        $fakeIntent2->setName('fake intent 2');
        $fakeIntent2->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setTransition(new Transition($fakeConversation->getUid(), null, null));
        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent2]));

        ConversationDataClient::shouldReceive('getScenarioByUid')
            ->once()
            ->with($fakeScenario->getUid(), false)
            ->andReturn($fakeScenario);

        $fakeIntent3 = new Intent();
        $fakeIntent3->setUid('0x1234');
        $fakeIntent3->setOdId('fake_intent_3');
        $fakeIntent3->setName('fake intent 3');
        $fakeIntent3->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setTransition(new Transition($fakeConversation->getUid(), null, null));

        $intentsWithTransitions = new IntentCollection([$fakeIntent2, $fakeIntent3]);

        TransitionDataClient::shouldReceive('getIncomingConversationTransitions')
            ->with($fakeConversation->getUid())
            ->andReturn($intentsWithTransitions);

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/scenario/' . $fakeScenario->getUid())
            ->assertJson([
                'focusedScenario' => [
                    'id'=> '0x0001',
                    'od_id'=> 'example_scenario',
                    'name'=> 'Example scenario',
                    'description'=> 'An example scenario',
                    'conversations' => [
                        [
                            "id" => "0x0002",
                            "name" => "New Example conversation",
                            "od_id" => "new_example_conversation",
                            "description" => "An new example conversation",
                            "behaviors" => ["STARTING"],
                            "_meta" => [
                                "incoming_transitions" => [
                                    [
                                        "id" => "0x9999",
                                        "od_id" => "fake_intent_2",
                                        "transition" => [
                                            "conversation" => "0x0002",
                                            "scene" => null,
                                            "turn" => null,
                                        ]
                                    ],
                                    [
                                        "id" => "0x1234",
                                        "od_id" => "fake_intent_3",
                                        "transition" => [
                                            "conversation" => "0x0002",
                                            "scene" => null,
                                            "turn" => null,
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ],
                    'labels' => [
                        'platform_components' => [WebchatPlatform::getComponentId()],
                        'platform_types' => ['text'],
                    ]
                ]
            ]);
    }

    public function testGetFocusedConversation()
    {
        $fakeScenario = new Scenario();
        $fakeScenario->setUid('0x0001');
        $fakeScenario->setName("Example scenario");
        $fakeScenario->setOdId('example_scenario');
        $fakeScenario->setDescription('An example scenario');

        $fakeConversation = new Conversation($fakeScenario);
        $fakeConversation->setUid('0x0002');
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setDescription("An new example conversation");
        $fakeConversation->setInterpreter('interpreter.core.nlp');
        $fakeConversation->setBehaviors(new BehaviorsCollection());
        $fakeConversation->setConditions(new ConditionCollection());
        $fakeConversation->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setScenario($fakeScenario);

        $fakeScene = new Scene($fakeConversation);
        $fakeScene->setUid('0x0003');
        $fakeScene->setName('New Example scene');
        $fakeScene->setOdId('new_example_scene');
        $fakeScene->setDescription("An new example scene");
        $fakeScene->setInterpreter('interpreter.core.nlp');
        $fakeScene->setBehaviors(new BehaviorsCollection([new Behavior(Behavior::OPEN_BEHAVIOR)]));
        $fakeScene->setConditions(new ConditionCollection());
        $fakeScene->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScene->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setScenes(new SceneCollection([$fakeScene]));

        $fakeTurn = new Turn($fakeScene);
        $fakeTurn->setUid('0x0004');
        $fakeTurn->setName('fake turn');
        $fakeTurn->setOdId('fake_scene');
        $fakeTurn->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeTurn->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScene->setTurns(new TurnCollection([$fakeTurn]));

        $fakeIntent = new Intent($fakeTurn);
        $fakeIntent->setUid('0x9998');
        $fakeIntent->setOdId('fake_intent_1');
        $fakeIntent->setName('fake intent 1');
        $fakeIntent->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent]));

        $fakeIntent2 = new Intent($fakeTurn);
        $fakeIntent2->setUid('0x9999');
        $fakeIntent2->setOdId('fake_intent_2');
        $fakeIntent2->setName('fake intent 2');
        $fakeIntent2->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setTransition(new Transition($fakeConversation->getUid(), $fakeScene->getUid(), null));

        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent2]));

        ConversationDataClient::shouldReceive('getConversationByUid')
            ->once()
            ->with($fakeConversation->getUid(), false)
            ->andReturn($fakeConversation);

        ConversationDataClient::shouldReceive('getScenarioWithFocusedConversation')
            ->once()
            ->with($fakeConversation->getUid())
            ->andReturn($fakeConversation);

        $fakeIntent3 = new Intent();
        $fakeIntent3->setUid('0x1234');
        $fakeIntent3->setOdId('fake_intent_3');
        $fakeIntent3->setName('fake intent 3');
        $fakeIntent3->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setTransition(new Transition($fakeConversation->getUid(), $fakeScene->getUid(), null));

        $intentsWithTransitions = new IntentCollection([$fakeIntent2, $fakeIntent3]);

        TransitionDataClient::shouldReceive('getIncomingSceneTransitions')
            ->with($fakeScene->getUid())
            ->andReturn($intentsWithTransitions);

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/conversation/' . $fakeConversation->getUid())
            ->assertJson([
                'scenario' => [
                    'id'=> '0x0001',
                    'od_id'=> 'example_scenario',
                    'name'=> 'Example scenario',
                    'description'=> 'An example scenario',
                    'focusedConversation' => [
                        "id" => "0x0002",
                        "name" => "New Example conversation",
                        "od_id" => "new_example_conversation",
                        "description" => "An new example conversation",
                        "interpreter" => "interpreter.core.nlp",
                        "behaviors" => [],
                        "conditions" => [],
                        "created_at" => "2021-03-12T11:57:23+0000",
                        "updated_at" => "2021-03-12T11:57:23+0000",
                        "scenes" => [
                            [
                                "id" => "0x0003",
                                "name" => "New Example scene",
                                "od_id" => "new_example_scene",
                                "description" => "An new example scene",
                                "behaviors" => ["OPEN"],
                                "_meta" => [
                                    "incoming_transitions" => [
                                        [
                                            "id" => "0x9999",
                                            "od_id" => "fake_intent_2",
                                            "transition" => [
                                                "conversation" => "0x0002",
                                                "scene" => "0x0003",
                                                "turn" => null,
                                            ]
                                        ],
                                        [
                                            "id" => "0x1234",
                                            "od_id" => "fake_intent_3",
                                            "transition" => [
                                                "conversation" => "0x0002",
                                                "scene" => "0x0003",
                                                "turn" => null,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function testGetFocusedScene()
    {
        $fakeScenario = new Scenario();
        $fakeScenario->setUid('0x0001');
        $fakeScenario->setName("Example scenario");
        $fakeScenario->setOdId('example_scenario');
        $fakeScenario->setDescription('An example scenario');

        $fakeConversation = new Conversation($fakeScenario);
        $fakeConversation->setUid('0x0002');
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setDescription("An new example conversation");
        $fakeConversation->setInterpreter('interpreter.core.nlp');
        $fakeConversation->setBehaviors(new BehaviorsCollection());
        $fakeConversation->setConditions(new ConditionCollection());
        $fakeConversation->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScenario->setConversations(new ConversationCollection([$fakeConversation]));

        $fakeScene = new Scene($fakeConversation);
        $fakeScene->setUid('0x0003');
        $fakeScene->setOdId('welcome_scene');
        $fakeScene->setName('Welcome scene');
        $fakeScene->setDescription('A welcome scene');
        $fakeScene->setInterpreter('interpreter.core.nlp');
        $fakeScene->setBehaviors(new BehaviorsCollection());
        $fakeScene->setConditions(new ConditionCollection());
        $fakeScene->setCreatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $fakeScene->setUpdatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $fakeConversation->setScenes(new SceneCollection([$fakeScene]));

        $fakeTurn = new Turn($fakeScene);
        $fakeTurn->setUid('0x0003');
        $fakeTurn->setOdId('welcome_turn');
        $fakeTurn->setName('Welcome turn');
        $fakeTurn->setDescription('A welcome turn');
        $fakeTurn->setInterpreter('interpreter.core.nlp');
        $fakeTurn->setBehaviors(new BehaviorsCollection([
            new Behavior(Behavior::STARTING_BEHAVIOR),
            new Behavior(Behavior::OPEN_BEHAVIOR)
        ]));

        $fakeScene->setTurns(new TurnCollection([$fakeTurn]));

        $fakeIntent = new Intent($fakeTurn);
        $fakeIntent->setUid('0x9998');
        $fakeIntent->setOdId('fake_intent_1');
        $fakeIntent->setName('fake intent 1');
        $fakeIntent->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setTransition(new Transition('0x567', null, null));
        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent]));

        $fakeIntent2 = new Intent($fakeTurn);
        $fakeIntent2->setUid('0x9999');
        $fakeIntent2->setOdId('fake_intent_2');
        $fakeIntent2->setName('fake intent 2');
        $fakeIntent2->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setTransition(new Transition($fakeConversation->getUid(), $fakeScene->getUid(), $fakeTurn->getUid()));

        $fakeIntent3 = new Intent($fakeTurn);
        $fakeIntent3->setUid('0x9999a');
        $fakeIntent3->setOdId('fake_intent_3');
        $fakeIntent3->setName('fake intent 3');
        $fakeIntent3->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setBehaviors(new BehaviorsCollection([new Behavior(Behavior::COMPLETING_BEHAVIOR)]));

        $fakeTurn->setResponseIntents(new IntentCollection([$fakeIntent2, $fakeIntent3]));

        SceneDataClient::shouldReceive('getFullSceneGraph')
            ->once()
            ->with($fakeScene->getUid())
            ->andReturn($fakeScene);

        ConversationDataClient::shouldReceive('getScenarioWithFocusedScene')
            ->once()
            ->with($fakeScene->getUid())
            ->andReturn($fakeScene);

        TransitionDataClient::shouldReceive('getIncomingTurnTransitions')
            ->with($fakeTurn->getUid())
            ->andReturn(new IntentCollection([$fakeIntent2]));

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/scene/' . $fakeScene->getUid())
            ->assertJson([
                'scenario' => [
                    'id'=> '0x0001',
                    'od_id'=> 'example_scenario',
                    'name'=> 'Example scenario',
                    'description'=> 'An example scenario',
                    'conversation' => [
                        "id" => "0x0002",
                        "od_id" => "new_example_conversation",
                        "name" => "New Example conversation",
                        "description" => "An new example conversation",
                        "focusedScene" => [
                            "id" => "0x0003",
                            "od_id"=> "welcome_scene",
                            "name"=> "Welcome scene",
                            "description"=> "A welcome scene",
                            "updated_at"=> "2021-02-24T09:30:00+0000",
                            "created_at"=> "2021-02-24T09:30:00+0000",
                            "interpreter" => 'interpreter.core.nlp',
                            "behaviors" => [],
                            "conditions" => [],
                            "turns" => [
                                [
                                    "id" => "0x0003",
                                    "od_id"=> "welcome_turn",
                                    "name"=> "Welcome turn",
                                    "description"=> "A welcome turn",
                                    "behaviors" => ["STARTING", "OPEN"],
                                    "_meta" => [
                                        "incoming_transitions" => [
                                            [
                                                "id" => "0x9999",
                                                "od_id" => "fake_intent_2",
                                                "transition" => [
                                                    "conversation" => "0x0002",
                                                    "scene" => "0x0003",
                                                    "turn" => "0x0003",
                                                ]
                                            ]
                                        ],
                                        "outgoing_transitions" => [
                                            [
                                                "id" => "0x9998",
                                                "od_id" => "fake_intent_1",
                                                "transition" => [
                                                    "conversation" => "0x567",
                                                    "scene" => null,
                                                    "turn" => null,
                                                ]
                                            ],
                                            [
                                                "id" => "0x9999",
                                                "od_id" => "fake_intent_2",
                                                "transition" => [
                                                    "conversation" => "0x0002",
                                                    "scene" => "0x0003",
                                                    "turn" => "0x0003",
                                                ]
                                            ]
                                        ],
                                        "completing_intents" => [
                                            [
                                                "id" => "0x9999a",
                                                "od_id" => "fake_intent_3",
                                                "behaviors" => [
                                                    Behavior::COMPLETING_BEHAVIOR,
                                                ]
                                            ]
                                        ],
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function testGetFocusedSceneNoIncomingTransitions()
    {
        $fakeScenario = new Scenario();
        $fakeScenario->setUid('0x0001');
        $fakeScenario->setName("Example scenario");
        $fakeScenario->setOdId('example_scenario');
        $fakeScenario->setDescription('An example scenario');

        $fakeConversation = new Conversation($fakeScenario);
        $fakeConversation->setUid('0x0002');
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setDescription("An new example conversation");
        $fakeConversation->setInterpreter('interpreter.core.nlp');
        $fakeConversation->setBehaviors(new BehaviorsCollection());
        $fakeConversation->setConditions(new ConditionCollection());
        $fakeConversation->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeConversation->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeScenario->setConversations(new ConversationCollection([$fakeConversation]));

        $fakeScene = new Scene($fakeConversation);
        $fakeScene->setUid('0x0003');
        $fakeScene->setOdId('welcome_scene');
        $fakeScene->setName('Welcome scene');
        $fakeScene->setDescription('A welcome scene');
        $fakeScene->setInterpreter('interpreter.core.nlp');
        $fakeScene->setBehaviors(new BehaviorsCollection());
        $fakeScene->setConditions(new ConditionCollection());
        $fakeScene->setCreatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $fakeScene->setUpdatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $fakeConversation->setScenes(new SceneCollection([$fakeScene]));

        $fakeTurn = new Turn($fakeScene);
        $fakeTurn->setUid('0x0003');
        $fakeTurn->setOdId('welcome_turn');
        $fakeTurn->setName('Welcome turn');
        $fakeTurn->setDescription('A welcome turn');
        $fakeTurn->setInterpreter('interpreter.core.nlp');
        $fakeTurn->setBehaviors(new BehaviorsCollection([
            new Behavior(Behavior::STARTING_BEHAVIOR),
            new Behavior(Behavior::OPEN_BEHAVIOR)
        ]));

        $fakeScene->setTurns(new TurnCollection([$fakeTurn]));

        $fakeIntent = new Intent($fakeTurn);
        $fakeIntent->setUid('0x9998');
        $fakeIntent->setOdId('fake_intent_1');
        $fakeIntent->setName('fake intent 1');
        $fakeIntent->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent->setTransition(new Transition('0x567', null, null));
        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent]));

        $fakeIntent2 = new Intent($fakeTurn);
        $fakeIntent2->setUid('0x9999');
        $fakeIntent2->setOdId('fake_intent_2');
        $fakeIntent2->setName('fake intent 2');
        $fakeIntent2->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent2->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));

        $fakeIntent3 = new Intent($fakeTurn);
        $fakeIntent3->setUid('0x9999a');
        $fakeIntent3->setOdId('fake_intent_3');
        $fakeIntent3->setName('fake intent 3');
        $fakeIntent3->setCreatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setUpdatedAt(Carbon::parse('2021-03-12T11:57:23+0000'));
        $fakeIntent3->setBehaviors(new BehaviorsCollection([new Behavior(Behavior::COMPLETING_BEHAVIOR)]));

        $fakeTurn->setResponseIntents(new IntentCollection([$fakeIntent2, $fakeIntent3]));

        SceneDataClient::shouldReceive('getFullSceneGraph')
            ->once()
            ->with($fakeScene->getUid())
            ->andReturn($fakeScene);

        ConversationDataClient::shouldReceive('getScenarioWithFocusedScene')
            ->once()
            ->with($fakeScene->getUid())
            ->andReturn($fakeScene);

        TransitionDataClient::shouldReceive('getIncomingTurnTransitions')
            ->with($fakeTurn->getUid())
            ->andReturn(new IntentCollection());

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/scene/' . $fakeScene->getUid())
            ->assertJson([
                'scenario' => [
                    'id'=> '0x0001',
                    'od_id'=> 'example_scenario',
                    'name'=> 'Example scenario',
                    'description'=> 'An example scenario',
                    'conversation' => [
                        "id" => "0x0002",
                        "od_id" => "new_example_conversation",
                        "name" => "New Example conversation",
                        "description" => "An new example conversation",
                        "focusedScene" => [
                            "id" => "0x0003",
                            "od_id"=> "welcome_scene",
                            "name"=> "Welcome scene",
                            "description"=> "A welcome scene",
                            "updated_at"=> "2021-02-24T09:30:00+0000",
                            "created_at"=> "2021-02-24T09:30:00+0000",
                            "interpreter" => 'interpreter.core.nlp',
                            "behaviors" => [],
                            "conditions" => [],
                            "turns" => [
                                [
                                    "id" => "0x0003",
                                    "od_id"=> "welcome_turn",
                                    "name"=> "Welcome turn",
                                    "description"=> "A welcome turn",
                                    "behaviors" => ["STARTING", "OPEN"],
                                    "_meta" => [
                                        "incoming_transitions" => [],
                                        "outgoing_transitions" => [
                                            [
                                                "id" => "0x9998",
                                                "od_id" => "fake_intent_1",
                                                "transition" => [
                                                    "conversation" => "0x567",
                                                    "scene" => null,
                                                    "turn" => null,
                                                ]
                                            ]
                                        ],
                                        "completing_intents" => [
                                            [
                                                "id" => "0x9999a",
                                                "od_id" => "fake_intent_3",
                                                "behaviors" => [
                                                    Behavior::COMPLETING_BEHAVIOR,
                                                ]
                                            ]
                                        ],
                                    ]
                                ],
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function testGetFocusedTurn()
    {
        $fakeTurn = $this->createFakeConversation('0x0001', '0x0002', '0x0003', '0x0004');

        $fakeIntent = new Intent($fakeTurn);
        $fakeIntent->setUid('0x0004');
        $fakeIntent->setOdId('first_intent');
        $fakeIntent->setName('First intent');
        $fakeIntent->setDescription('The first intent');
        $fakeIntent->setCreatedAt($fakeTurn->getCreatedAt());
        $fakeIntent->setUpdatedAt($fakeTurn->getUpdatedAt());

        $fakeTurn->setRequestIntents(new IntentCollection([$fakeIntent]));

        $message1 = new MessageTemplate();
        $message1->setIntent($fakeIntent);
        $message1->setUid('0x0005');
        $message1->setOdId('first');
        $message1->setName('First');
        $message1->setCreatedAt($fakeIntent->getCreatedAt());
        $message1->setUpdatedAt($fakeIntent->getUpdatedAt());

        $message2 = new MessageTemplate();
        $message2->setIntent($fakeIntent);
        $message2->setUid('0x0006');
        $message2->setOdId('second');
        $message2->setName('Second');
        $message2->setCreatedAt($fakeIntent->getCreatedAt());
        $message2->setUpdatedAt($fakeIntent->getUpdatedAt());

        $fakeIntent->setMessageTemplates(new MessageTemplateCollection([$message1, $message2]));

        ConversationDataClient::shouldReceive('getTurnByUid')
            ->once()
            ->with($fakeTurn->getUid(), false)
            ->andReturn($fakeTurn);

        ConversationDataClient::shouldReceive('getScenarioWithFocusedTurn')
            ->once()
            ->with($fakeTurn->getUid())
            ->andReturn($fakeTurn);

        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/focused/turn/' . $fakeTurn->getUid())
            ->assertJson([
                'scenario' => [
                    'id'=> '0x0001',
                    'od_id'=> 'example_scenario',
                    'name'=> 'Example scenario',
                    'description'=> 'An example scenario',
                    'conversation' => [
                        "id" => "0x0002",
                        "od_id" => "new_example_conversation",
                        "name" => "New Example conversation",
                        "description" => "An new example conversation",
                        "scene" => [
                            "id" => "0x0003",
                            "od_id"=> "welcome_scene",
                            "name"=> "Welcome scene",
                            "description"=> "A welcome scene",
                            "interpreter" => 'interpreter.core.nlp',
                            "focusedTurn" => [
                                "id" => "0x0004",
                                "od_id" => "first_turn",
                                "name" => "First turn",
                                "description" => "The first turn",
                                "updated_at"=> "2021-02-24T09:30:00+0000",
                                "created_at"=> "2021-02-24T09:30:00+0000",
                                "behaviors" => [],
                                "conditions" => [],
                                "intents" => [
                                    [
                                        "order" => "REQUEST",
                                        "intent" => [
                                            "id" => "0x0004",
                                            "od_id" => "first_intent",
                                            "name" => "First intent",
                                            "description" => "The first intent",
                                            "behaviors" => [],
                                            "conditions" => [],
                                            "message_templates" => [
                                                [
                                                    "id" => "0x0005",
                                                ],
                                                [
                                                    "id" => "0x0006",
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function testGetConversationTreeByScenario()
    {
        $fakeTurn = new Turn();
        $fakeTurn->setUid('0x0004');
        $fakeTurn->setOdId('first_turn');
        $fakeTurn->setName('First turn');

        $fakeScene = new Scene();
        $fakeScene->setUid('0x0003');
        $fakeScene->setOdId('welcome_scene');
        $fakeScene->setName('Welcome scene');
        $fakeScene->addTurn($fakeTurn);

        $fakeConversation = new Conversation();
        $fakeConversation->setUid('0x0002');
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setScenes(new SceneCollection([$fakeScene]));

        $fakeScenario = new Scenario();
        $fakeScenario->setUid('0x0001');
        $fakeScenario->setConversations(new ConversationCollection([$fakeConversation]));

        ConversationDataClient::shouldReceive('getScenarioByUid')
            ->once()
            ->with($fakeScenario->getUid(), false)
            ->andReturn($fakeScenario);

        ConversationDataClient::shouldReceive('getConversationTreeByScenarioUid')
            ->once()
            ->with($fakeScenario->getUid())
            ->andReturn($fakeScenario);


        $this->actingAs($this->user, 'api')
            ->json('GET', '/admin/api/conversation-builder/ui-state/scenarios/' . $fakeScenario->getUid() . '/tree')
            ->assertExactJson([
                "id" => "0x0001",
                "conversations" => [
                    [
                        "id" => "0x0002",
                        "od_id" => "new_example_conversation",
                        "name" => "New Example conversation",
                        "scenes" => [
                            [
                                "id" => "0x0003",
                                "od_id" => "welcome_scene",
                                "name" => "Welcome scene",
                                "turns" => [
                                    [
                                        "id" => "0x0004",
                                        "od_id" => "first_turn",
                                        "name" => "First turn"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function testMassUpdateNoRequestResponse()
    {
        ConversationDataClient::shouldReceive('getTurnByUid')->never();

        $this->actingAs($this->user, 'api')
            ->patch('/admin/api/conversation-builder/ui-state/turns/0x0001/intents/neither', [])
            ->assertStatus(404);
    }

    public function testMassUpdateNonValidParticipant()
    {
        $fakeTurn = new Turn();
        $fakeTurn->setUid('0x0001');
        $fakeTurn->setOdId('welcome_turn');
        $fakeTurn->setName('Welcome Turn');
        $fakeTurn->setDescription('A welcome turn');

        ConversationDataClient::shouldReceive('getTurnByUid')
            ->once()
            ->with($fakeTurn->getUid(), false)
            ->andReturn($fakeTurn);

        $body = ['participant' => 'INVALID'];

        $this->actingAs($this->user, 'api')
            ->json('PATCH', '/admin/api/conversation-builder/ui-state/turns/0x0001/intents/request', $body)
            ->assertStatus(422);
    }

    public function testMassIntentUpdate()
    {
        $turnUid = '0x0004';

        $turn = $this->createFakeConversation('0x0001', '0x0002', '0x0003', $turnUid);

        $requestIntent = new Intent($turn);
        $requestIntent->setUid('0x0005');
        $requestIntent->setOdId('welcome_intent_1');
        $requestIntent->setName('Welcome intent 1');
        $requestIntent->setDescription('A welcome intent 1');
        $requestIntent->setCreatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $requestIntent->setUpdatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $requestIntent->setSpeaker(Intent::USER);
        $requestIntent->setSampleUtterance('Hello!');
        $turn->addRequestIntent($requestIntent);

        $responseIntent = new Intent($turn);
        $responseIntent->setUid('0x0006');
        $responseIntent->setOdId('goodbye_intent_1');
        $responseIntent->setName('Goodbye intent 1');
        $responseIntent->setDescription('A goodbye intent 1');
        $responseIntent->setCreatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $responseIntent->setUpdatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $responseIntent->setSpeaker(Intent::APP);
        $responseIntent->setSampleUtterance('Welcome user!');
        $turn->addResponseIntent($responseIntent);

        ConversationDataClient::shouldReceive('getTurnByUid')
            ->once()
            ->with($turn->getUid(), false)
            ->andReturn($turn);

        $responseIntent->setSpeaker(Intent::USER);
        ConversationDataClient::shouldReceive('updateIntent')
            ->once()
            ->with($responseIntent);

        $requestIntent->setSpeaker(Intent::APP);
        ConversationDataClient::shouldReceive('updateIntent')
            ->once()
            ->with($requestIntent);


        $turn->setRequestIntents(new IntentCollection());
        $turn->addRequestIntent($requestIntent);

        $turn->setResponseIntents(new IntentCollection());
        $turn->addResponseIntent($responseIntent);
        ConversationDataClient::shouldReceive('getScenarioWithFocusedTurn')
            ->once()
            ->andReturn($turn);

        $body = ['participant' => 'APP'];

        $this->actingAs($this->user, 'api')
            ->json('PATCH', "/admin/api/conversation-builder/ui-state/turns/$turnUid/intents/request", $body)
            ->assertStatus(200)
            ->assertJsonFragment([
                "intents" => [
                    [
                        "intent" => [
                            "description" => "A goodbye intent 1",
                            "id" => "0x0006",
                            "name" => "Goodbye intent 1",
                            "od_id" => "goodbye_intent_1",
                            "sample_utterance" => "Welcome user!",
                            "speaker" => "USER",
                            "behaviors" => [],
                            "conditions" => [],
                            "message_templates" => [],
                        ],
                        "order" => "RESPONSE"
                    ],
                    [
                        "intent" => [
                            "description" => "A welcome intent 1",
                            "id" => "0x0005",
                            "name" => "Welcome intent 1",
                            "od_id" => "welcome_intent_1",
                            "sample_utterance" => "Hello!",
                            "speaker" => "APP",
                            "behaviors" => [],
                            "conditions" => [],
                            "message_templates" => [],
                        ],
                        "order" => "REQUEST"
                    ]
                ]
            ]);
    }

    /**
     * @param $scenarioUid
     * @param $conversationUid
     * @param $sceneUid
     * @param $turnUid
     * @return Turn
     */
    public function createFakeConversation($scenarioUid, $conversationUid, $sceneUid, $turnUid): Turn
    {
        $fakeScenario = new Scenario();
        $fakeScenario->setUid($scenarioUid);
        $fakeScenario->setName("Example scenario");
        $fakeScenario->setOdId('example_scenario');
        $fakeScenario->setDescription('An example scenario');

        $fakeConversation = new Conversation();
        $fakeConversation->setUid($conversationUid);
        $fakeConversation->setName('New Example conversation');
        $fakeConversation->setOdId('new_example_conversation');
        $fakeConversation->setDescription("An new example conversation");

        $fakeConversation->setScenario($fakeScenario);

        $fakeScene = new Scene();
        $fakeScene->setUid($sceneUid);
        $fakeScene->setOdId('welcome_scene');
        $fakeScene->setName('Welcome scene');
        $fakeScene->setDescription('A welcome scene');
        $fakeScene->setInterpreter('interpreter.core.nlp');

        $fakeScene->setConversation($fakeConversation);

        $fakeTurn = new Turn();
        $fakeTurn->setUid($turnUid);
        $fakeTurn->setOdId('first_turn');
        $fakeTurn->setName('First turn');
        $fakeTurn->setDescription('The first turn');
        $fakeTurn->setInterpreter('interpreter.core.nlp');
        $fakeTurn->setBehaviors(new BehaviorsCollection());
        $fakeTurn->setConditions(new ConditionCollection());
        $fakeTurn->setCreatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));
        $fakeTurn->setUpdatedAt(Carbon::parse('2021-02-24T09:30:00+0000'));

        $fakeTurn->setRequestIntents(new IntentCollection());
        $fakeTurn->setResponseIntents(new IntentCollection());

        $fakeTurn->setScene($fakeScene);
        return $fakeTurn;
    }
}
