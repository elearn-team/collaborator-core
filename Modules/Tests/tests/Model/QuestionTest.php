<?php

namespace Modules\Tests\Tests\Model;

use Modules\Tests\Model\Question;
use Modules\Tests\Model\Result;
use Modules\Tests\Model\Answer;
use Modules\Tests\Model\Test;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tests\Model\ResultRefAnswer;
use Modules\Tasks\Model\TaskTestSetting;
use Modules\Tasks\Model\Task;
use Bazalt\Auth\Model\User;
use Bazalt\Auth;

class QuestionTest extends \PHPUnit_Framework_TestCase
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
    protected $user;
    protected $testResult;
    protected $answer1;
    protected $answer2;
    protected $answer3;

    public function setUp() {

        $this->model = new Question();

        $this->user = new User();
        $this->user->login = 'test';
        $this->user->email = 'test@test.test';
        $this->user->is_active= 1;
        $this->user->save();
        $this->models []= $this->user;

        \Bazalt\Auth::setUser($this->user);

        $this->test = Test::create();
        $this->test->title = 'bla-bla-bla';
        $this->test->description = 'test';
        $this->test->save();
        $this->models []= $this->test;

        $this->task = Task::create();
        $this->task->title = 'bla-bla-bla';
        $this->task->type = Task::TYPE_TEST;
        $this->task->element_id = $this->test->id;
        $this->task->save();
        $this->models []= $this->task;

        $this->taskSett = TaskTestSetting::create($this->task->id);
        $this->taskSett->all_questions = 1;
        $this->taskSett->unlim_attempts = 1;
        $this->taskSett->save();
        $this->models []= $this->taskSett;

        $this->question = Question::create();
        $this->question->id = $this->test->id;
        $this->question->test_id = $this->test->id;
        $this->question->type = 'multi';
        $this->question->body = 'test';
        $this->question->save();
        $this->models [] = $this->question;

        $this->model->id = $this->question->id;

        TaskRefUser::assignUser((int)$this->task->id, (int)$this->user->id);


        $this->answer1 = Answer::create();
        $this->answer1->question_id = $this->question->id;
        $this->answer1->body = 'Ответ 1';
        $this->answer1->save();
        $this->models []= $this->answer1;

        $this->answer2 = Answer::create();
        $this->answer2 ->question_id = $this->question->id;
        $this->answer2 ->body = 'Ответ 2';
        $this->answer2 ->is_right = 1;
        $this->answer2 ->save();
        $this->models []= $this->answer2;

        $this->answer3 = Answer::create();
        $this->answer3->question_id = $this->question->id;
        $this->answer3->body = 'Ответ 3';
        $this->answer3->is_right = 1;
        $this->answer3->save();
        $this->models []= $this->answer3;


        $result = Result::create($this->test->id);
        $result->status = 'finished';
        $result->save();

        $this->models []= $result;

        $this->id = $result->id;
        $this->status = $result->status;

        parent::setUp() ;
    }

    public function tearDown()
    {
        foreach($this->models as $o) {
            $o->delete();
        }

        parent::tearDown();
    }

    public function testGetResultSingle(){

        //1 відповідь правильна------------------------------------------------
        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer1->id;
        $ref->is_right = $this->answer1->is_right;
        $ref->save();

        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer3->id;
        $ref->is_right = $this->answer3->is_right;
        $ref->save();

        $this->userTask = TaskRefUser::getByUserAndTask($this->task->id, $this->user->id);

        $res = Result::getLastFinished($this->test->id);

        $this->assertEquals(0.33333333333333,$this->model->getResultMulti($res));

    }

    public function testGetResultSingle2(){

//Немає правильних відповідей-------------------------------------------
        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer1->id;
        $ref->is_right = $this->answer1->is_right;
        $ref->save();

        $this->userTask = TaskRefUser::getByUserAndTask($this->task->id, $this->user->id);

        $res = Result::getLastFinished($this->test->id);

        $this->assertEquals(0,$this->model->getResultMulti($res));

    }

    public function testGetResultSingle3(){


        //Всі відповіді правильні----------------------------------------------------------
        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer2->id;
        $ref->is_right = $this->answer2->is_right;
        $ref->save();

        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer3->id;
        $ref->is_right = $this->answer3->is_right;
        $ref->save();

        $this->userTask = TaskRefUser::getByUserAndTask($this->task->id, $this->user->id);

        $res = Result::getLastFinished($this->test->id);

        $this->assertEquals(1,$this->model->getResultMulti($res));

    }

    public function testGetResultSingle4(){

        //Всі відповіді вибрані----------------------------------------------------------
        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer3->id;
        $ref->is_right = $this->answer3->is_right;
        $ref->save();

        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer2->id;
        $ref->is_right = $this->answer2->is_right;
        $ref->save();

        $ref = new ResultRefAnswer();
        $ref->result_id = $this->id;
        $ref->question_id = $this->question->id;
        $ref->answer_id = $this->answer1->id;
        $ref->is_right = $this->answer1->is_right;
        $ref->save();

        $this->userTask = TaskRefUser::getByUserAndTask($this->task->id, $this->user->id);

        $res = Result::getLastFinished($this->test->id);

        $this->assertEquals(0.66666666666667,$this->model->getResultMulti($res));

    }


}