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

class QuestionResourceTest extends \Bazalt\Auth\Test\BaseCase
{

    protected $question;
    protected $test;
    protected $resQuestion;


    protected function setUp()
    {
        parent::setUp();
        $this->initApp(getWebServices());

        $this->test = new Test();
        $this->test->site_id = 1;
        $this->test->title = 'test';
        $this->test->save();
        $this->models[] = $this->test;

        $this->question = new Question();
        $this->question->site_id = 1;
        $this->question->test_id = $this->test->id;
        $this->question->type = 'free';
        $this->question->weight = 1;
        $this->question->body = 'question';
        $this->question->save();
        $this->models[] = $this->question;

        $this->resQuestion = $this->question->toArray();


        for($i=0; $i < 2; $i++){
            $answer = new Answer();
            $answer->site_id = 1;
            $answer->question_id = $this->question->id;
            $answer->is_right = 1;
            $answer->body = 'answer_'.$i;
            $answer->save();
            $this->models[] = $answer;
        }

    }

    public function tearDown()
    {
        parent::tearDown();
    }


    public function testGetItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Question "9999" not found'
        ]);
        $this->assertResponse('GET /tests/questions/9999', [], $response);

        $response = new \Bazalt\Rest\Response(200, $this->resQuestion);
        $this->assertResponse('GET /tests/questions/' . $this->question->id, [], $response);
    }

    public function testSaveItem()
    {
        $response = new \Bazalt\Rest\Response(Response::FORBIDDEN, 'Permission denied');

        $this->assertResponse('POST /tests/questions/'. $this->question->id, [
            'data' => json_encode(array(
                'id' =>  $this->question->id,
                'weight' => 1,
                'type' => 'single',
                'test_id' => $this->test->id,
                'body' => 'new question'
            ))
        ], $response);

        $this->addPermission('tests.can_manage_tests');

        $response = new \Bazalt\Rest\Response(400, ['id' => ['required' => 'Field cannot be empty']]);

        $this->assertResponse('POST /tests/questions/'. $this->question->id, [
            'data' => json_encode(array(
                'weight' => 1,
                'type' => 'single',
                'test_id' => $this->test->id,
                'body' => 'new question'
            ))
        ], $response);


        $this->resQuestion['type'] = 'single';
        $this->resQuestion['body'] = 'new question';
        $response = new \Bazalt\Rest\Response(200, $this->resQuestion);

        $this->assertResponse('POST /tests/questions/'. $this->question->id, [
            'data' => json_encode(array(
                'id' =>  $this->question->id,
                'weight' => 1,
                'type' => 'single',
                'test_id' => $this->test->id,
                'body' => 'new question'
            ))
        ], $response);

    }

    public function testDeleteItem(){
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Question "9999" not found'
        ]);
        $this->assertResponse('DELETE /tests/questions/9999', [], $response);

        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /tests/questions/' . $this->question->id, [], $response);
    }

    public function testCheckAnswer(){

    }


}