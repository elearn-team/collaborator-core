<?php

namespace Modules\Tests\Tests\Webservice;

use Bazalt\Rest;
use Modules\Tests\Model\Result;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;
use Modules\Tests\Model\Test;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Model\TaskTestSetting;
use Modules\Tasks\Model\Task;
use Tonic;
use Tonic\Response;

class ResultResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $task;
    protected $test;
    protected $taskSett;
    protected $userTask;
    protected $res;
    protected $result;
    protected $question;
    protected $answer;
    protected $answers;


    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->test = Test::create();
        $this->test->title = 'bla-bla-bla';
        $this->test->description = 'test';
        $this->test->save();
        $this->models [] = $this->test;

        $this->task = Task::create();
        $this->task->title = 'bla-bla-bla';
        $this->task->type = Task::TYPE_TEST;
        $this->task->element_id = $this->test->id;
        $this->task->save();
        $this->models [] = $this->task;

        $this->taskSett = TaskTestSetting::create($this->task->id);
        $this->taskSett->all_questions = 1;
        $this->taskSett->unlim_attempts = 1;
        $this->taskSett->save();
        $this->models [] = $this->taskSett;


        $this->question = Question::create();
        $this->question->test_id = $this->test->id;
        $this->question->type = 'single';
        $this->question->body = 'test';
        $this->question->save();
        $this->models [] = $this->question;

        $this->answer = Answer::create();
        $this->answer->question_id = $this->question->id;
        $this->answer->body = 'Ответ';
        $this->answer->save();
        $this->models [] = $this->answer;

        $this->question = Question::create();
        $this->question->test_id = $this->test->id;
        $this->question->type = 'single';
        $this->question->body = 'test2';
        $this->question->save();
        $this->models [] = $this->question;

        $this->answer = Answer::create();
        $this->answer->question_id = $this->question->id;
        $this->answer->body = 'Ответ2';
        $this->answer->save();
        $this->models [] = $this->answer;

        TaskRefUser::assignUser((int)$this->task->id, (int)$this->user->id);


    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetItemByTaskId()
    {
        $response = new \Bazalt\Rest\Response(400, [
                'not_found' => 'Task "9999" not found'
            ]
        );
        $_GET['action'] = 'get-by-task';
        $this->assertResponse('GET /tests/9999/results', [], $response);


        $testArr = $this->test->toArray();
        $testArr['answers_received'] = 0;
        $testArr['percent_complete'] = 0;
        $testArr['mark'] = 0;

        $_GET['action'] = 'get-by-task';
        list($code, $retResponse) = $this->send('GET /tests/' . $this->task->id . '/results', []);
        $this->assertEquals(200, $code);
        $this->assertEquals($testArr, $retResponse['test']);
        $this->assertEquals($this->taskSett->toArray(), $retResponse['settings']);
        $this->assertEquals(2, $retResponse['questionsCount']);
        $this->assertEquals([
            'canFinish' => false
        ], $retResponse['testSession']);
    }

    public function testGetRandomQuestion()
    {
        $response = new \Bazalt\Rest\Response(400, [
                'not_found' => 'Task "9999" not found'
            ]
        );
        $_GET['action'] = 'random-question';
        $this->assertResponse('GET /tests/9999/results', [], $response);

        $response = new \Bazalt\Rest\Response(400, [
                'session' => 'No active test session found']
        );
        $_GET['action'] = 'random-question';
        $this->assertResponse('GET /tests/' . $this->task->id . '/results', [], $response);


        $result = Result::create($this->test->id);
        $result->save();
        $this->models [] = $result;

        $res = [];
        $res['id'] = $this->question->id;
        $res['type'] = $this->question->type;
        $res['body'] = $this->question->body;

        $res['answers'] [] = [
            'id' => $this->answer->id,
            'body' => $this->answer->body
        ];

        list($code, $retResponse) = $this->send('GET /tests/' . $this->task->id . '/results', [], $response);
        $_GET['action'] = 'random-question';
        if (is_array($retResponse)) {
            $res = true;
        }
        $this->assertEquals(true, $res);

    }

    public function testCreateItem()
    {
        $response = new \Bazalt\Rest\Response(400, ['not_found' => 'Task "999" not found']);
        $this->assertResponse('POST /tests/999/results', [], $response);

        list($code, $retResponse) = $this->send('POST /tests/' . $this->task->id . '/results', [], $response);
        if (isset($retResponse) && !empty($retResponse)) {
            $res = true;
        }
        $this->assertEquals(true, $res);
    }

    public function testSaveResult()
    {
        $response = new \Bazalt\Rest\Response(400, ['not_found' => 'Task "999" not found']);
        $this->assertResponse('PUT /tests/999/results', [], $response);


        $response = new \Bazalt\Rest\Response(400, ['session' => 'No active test session found']);
        $this->assertResponse('PUT /tests/' . $this->task->id . '/results', [], $response);

        list($code, $retResponse) = $this->send('POST /tests/' . $this->task->id . '/results', [], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'question_id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('PUT /tests/' . $this->task->id . '/results', [
                'data' => json_encode(array(
                    'question_id' => ''
                ))
            ], $response);
    }


}