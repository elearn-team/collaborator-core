<?php

namespace Modules\Tests\Tests\Webservice;

use Bazalt\Rest;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Webservice\TaskSettingResource;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Result;
use Modules\Tests\Model\ResultRefAnswer;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Answer;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Tonic;
use Tonic\Response;
use Bazalt\Auth\Model\User;
use Modules\Tasks\Model\TaskTestSetting;

class TestsResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $tests;
    protected $testUser;
    protected $testRes = [];
    protected $answerRes = [];

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->testUser = User::create();
        $this->testUser->login = 'test';
        $this->testUser->is_active = true;
        $this->testUser->save();
        $this->models [] = $this->testUser;

        for ($i = 0; $i < 2; $i++) {
            $test = new Test();
            $test->site_id = 1;
            $test->title = 'test_' . $i;
            $test->save();
            $this->models [] = $test;

            $res = $test->toArray();
            $res['questions_count'] = 2;
            $res['tags'] = '';
            $this->testRes['data'][] = $res;

            for ($q = 0; $q < 2; $q++) {
                $question = new Question();
                $question->site_id = 1;
                $question->test_id = $test->id;
                $question->type = 'free';
                $question->weight = 1;
                $question->body = 'question_' . $i . '_' . $q;
                $question->save();
                $this->models[] = $question;

            }

            $task = Task::create();
            $task->site_id = 1;
            $task->title = 'task_' . $i;
            $task->description = 'task_' . $i;
            $task->type = 'test';
            $task->element_id = $test->id;
            $task->threshold = 50;
            $task->save();
            $this->models[] = $task;

            $taskSett = TaskTestSetting::create((int)$task->id);
            $taskSett->site_id = 1;
            $taskSett->all_questions = true;
            $taskSett->questions_count = 1;
            $taskSett->unlim_attempts = true;
            $taskSett->attempts_count = 1;
            $taskSett->time = null;
            $taskSett->time = 1;
            $taskSett->training = null;
            $taskSett->save();
            $this->models[] = $taskSett;

            TaskRefUser::assignUser((int)$task->id, (int)$this->testUser->id);

            $taskRefUser = TaskRefUser::getByUserAndTask((int)$task->id, (int)$this->testUser->id);
            $taskRefUser->status = 'verification';
            $taskRefUser->save();
            $this->models[] = $taskRefUser;


            $result = new Result();
            $result->site_id = 1;
            $result->test_id = $test->id;
            $result->task_id = $task->id;
            $result->user_id = $this->testUser->id;
            $result->status = 'verification';
            $result->settings = serialize($taskSett->toArray());
            $result->save();
            $this->models[] = $result;

            $testQuestions = Question::getCollection($test->id)->fetchAll();
            foreach ($testQuestions as $itm) {
                $resultRefAnswers = new ResultRefAnswer();
                $resultRefAnswers->result_id = $result->id;
                $resultRefAnswers->question_id = $itm->id;
                $resultRefAnswers->answer_id = 0;
                $resultRefAnswers->is_right = 0;
                $resultRefAnswers->mark = null;
                $resultRefAnswers->save();
                $this->models[] = $resultRefAnswers;


                $this->answerRes['data'][] = [
                    'id' => $resultRefAnswers->id,
                    'result_id' => 0,
                    'question_id' => $itm->id,
                    'answer_id' => 0,
                    'is_right' => 0,
                    'text_answer' => '',
                    'mark' => '',
                    'created_at' => 000,
                    'updated_at' => 000,
                    'created_by' => 0,
                    'updated_by' => 0,
                    'question' => $itm->body,
                    'weight' => $itm->weight,
                    'type' => 'test',
                    'title' => $task->title,
                    'threshold' => 50,
                    'test_title' => $test->title,
                    'firstname' => '',
                    'secondname' => '',
                    'patronymic' => '',
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'email' => '',
                    'files' => []
                ];
            }


        }

        $this->testRes['pager'] = [
            'current' => 1,
            'count' => 1,
            'total' => 2,
            'countPerPage' => 10
        ];

        $this->answerRes['pager'] = [
            'current' => 1,
            'count' => 1,
            'total' => 4,
            'countPerPage' => 10
        ];


    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetListTests()
    {
        $response = new \Bazalt\Rest\Response(
            Response::FORBIDDEN, 'Permission denied'
        );
        $this->assertResponse('GET /tests', [], $response);

        $this->addPermission('tests.can_manage_tests');

        $response = new \Bazalt\Rest\Response(Response::OK, $this->testRes);
        $this->assertResponse('GET /tests', [], $response);

    }

    public function testCreateTests()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /tests', [
            'data' => json_encode(array(
                'title' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(
            Response::FORBIDDEN, 'Permission denied'
        );
        $this->assertResponse('POST /tests', [
            'data' => json_encode(array(
                'title' => 'bla - bla - bla'
            ))
        ], $response);

        $this->addPermission('tests.can_manage_tests');

        list($code, $retResponse) = $this->send('POST /tests', [
            'data' => json_encode(array(
                'title' => 'bla - bla - bla'
            ))
        ]);
        $this->assertEquals('bla - bla - bla', $retResponse['title']);
    }

    public function testDeleteMultiTest()
    {
        $response = new \Bazalt\Rest\Response(
            Response::FORBIDDEN, 'Permission denied'
        );
        $_GET['action'] = 'deleteMulti';
        $this->assertResponse('POST /tests', [
            'data' => json_encode(array(
                'ids' => ''
            ))
        ], $response);

        $this->addPermission('tests.can_manage_tests');

        $response = new \Bazalt\Rest\Response(400, [0 => 'Must be array']);
        $_GET['action'] = 'deleteMulti';
        $this->assertResponse('POST /tests', [
            'data' => json_encode(array(
                'ids' => ''
            ))
        ], $response);

        $ids = [];
        foreach ($this->testRes['data'] as $itm) {
            $ids[] = $itm['id'];
        }
        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'deleteMulti';
        $this->assertResponse('POST /tests', [
            'data' => json_encode(array(
                'ids' => $ids
            ))
        ], $response);
    }

    public function testGetAnswersListForEvaluation()
    {
        $response = new \Bazalt\Rest\Response(
            Response::FORBIDDEN, 'Permission denied'
        );
        $_GET['action'] = 'getAnswersListForEvaluation';
        $this->assertResponse('GET /tests', [], $response);

        $this->addPermission('tests.can_manage_tests');

        $response = new \Bazalt\Rest\Response(Response::OK, $this->answerRes);
        $_GET['action'] = 'getAnswersListForEvaluation';
        $this->assertResponse('GET /tests', [], $response);
    }

    public function testGetAnswersListForEvaluationDashboard()
    {
//        $response = new \Bazalt\Rest\Response(
//            Response::FORBIDDEN, 'Permission denied'
//        );
//        $_GET['action'] = 'getAnswersListForEvaluationDashboard';
//        $this->assertResponse('GET /tests', [], $response);
//
//        $this->addPermission('tests.can_manage_tests');
//
//        $response = new \Bazalt\Rest\Response(Response::OK, $this->answerRes);
//        $_GET['action'] = 'getAnswersListForEvaluation';
//        $this->assertResponse('GET /tests', [], $response);
    }

}