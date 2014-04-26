<?php

namespace Modules\Tasks\Tests\Model;

use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Model\TaskTestSetting;
use Modules\Tasks\Model\Task;
use Modules\Resources\Model\Page;
use Modules\Tests\Model\Test;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseTestSetting;

use Bazalt\Auth\Model\User;
use Bazalt\Auth;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    protected $resource;
    protected $resourceTask;

    protected $test;
    protected $testTask;

    protected $course;
    protected $courseTask;

    protected $user = null;
    protected $models = [];

    public function setUp()
    {
        $this->user = new User();
        $this->user->login = 'test';
        $this->user->email = 'test@test.test';
        $this->user->is_active= 1;
        $this->user->save();
        $this->models []= $this->user;
        \Bazalt\Auth::setUser($this->user);

        $this->resource = Page::create();
        $this->resource->type = 'page';
        $this->resource->title = 'test page';
        $this->resource->description = 'test page';
        $this->resource->save();
        $this->resource = Page::getById($this->resource->id);
        $this->models []= $this->resource;

        $this->resourceTask = Task::create();
        $this->resourceTask->element_id = $this->resource->id;
        $this->resourceTask->type = Task::TYPE_RESOURCE;
        $this->resourceTask->threshold = 75;
        $this->resourceTask->title = 'Test task';
        $this->resourceTask->description = 'Test task description';
        $this->resourceTask->save();
        $this->resourceTask = Task::getById($this->resourceTask->id);
        $this->models []= $this->resourceTask;


        $this->test = Test::create();
        $this->test->title = 'some test 1';
        $this->test->description = 'some test 1';
        $this->test->save();
        $this->test = Test::getById($this->test->id);
        $this->models []= $this->test;

        $this->testTask = Task::create();
        $this->testTask->element_id = $this->test->id;
        $this->testTask->type = Task::TYPE_TEST;
        $this->testTask->threshold = 75;
        $this->testTask->title = 'Test task';
        $this->testTask->description = 'Test task description';
        $this->testTask->save();
        $this->testTask = Task::getById($this->testTask->id);
        $this->models []= $this->testTask;


        $this->course = Course::create();
        $this->course->title = 'some course';
        $this->course->description = 'some course';
        $this->course->save();
        $this->course = Course::getById($this->course->id);
        $this->models []= $this->course;

        $courseEl = CourseElement::create($this->course->id);
        $courseEl->element_id = (int)$this->resource->id;
        $courseEl->type = Task::TYPE_RESOURCE;
        $courseEl->order = 1;
        $courseEl->save();

        $plan = CoursePlan::create($this->course->id);
        $plan->element_id = (int)$this->resource->id;
        $plan->type = Task::TYPE_RESOURCE;
        $plan->order = 1;
        $plan->save();

        $courseEl = CourseElement::create($this->course->id);
        $courseEl->element_id = (int)$this->test->id;
        $courseEl->type = Task::TYPE_TEST;
        $courseEl->order = 2;
        $courseEl->save();

        $plan = CoursePlan::create($this->course->id);
        $plan->element_id = (int)$this->test->id;
        $plan->type = Task::TYPE_TEST;
        $plan->order = 2;
        $plan->save();

        $taskSett = CourseTestSetting::create((int)$this->course->id, (int)$this->test->id);
        $taskSett->all_questions = true;
        $taskSett->unlim_attempts = false;
        $taskSett->attempts_count = 2;
        $taskSett->save();

        $this->courseTask = Task::create();
        $this->courseTask->element_id = $this->course->id;
        $this->courseTask->type = Task::TYPE_COURSE;
        $this->courseTask->threshold = 75;
        $this->courseTask->title = 'Course task';
        $this->courseTask->description = 'Course task description';
        $this->courseTask->saveForCourse();
        $this->courseTask = Task::getById($this->courseTask->id);
        $this->models []= $this->courseTask;

        parent::setUp();
    }

    public function tearDown()
    {
        foreach($this->models as $o) {
            $o->delete();
        }

        parent::tearDown();
    }

    public function testToSmallArray()
    {
        $this->assertEquals([
            'id' => $this->resourceTask->id,
            'type' => $this->resourceTask->type,
            'title' => $this->resourceTask->title,
            'status' => null,
            'mark' => 0,
            'threshold' => (float)$this->resourceTask->threshold,
            'is_success' => '',
            'plan' => [],
            'can_execute' => true,
            'attempts_limit' => 0,
            'attempts_count' => 0
        ], $this->resourceTask->toSmallArray());

        TaskRefUser::assignUser($this->resourceTask->id, $this->user->id);
        $ref = TaskRefUser::getByUserAndTask($this->resourceTask->id, $this->user->id);
        $ref->status = TaskRefUser::STATUS_FINISHED;
        $ref->mark = 25;
        $ref->save();

        $this->assertEquals([
            'id' => $this->resourceTask->id,
            'type' => $this->resourceTask->type,
            'title' => $this->resourceTask->title,
            'status' => TaskRefUser::STATUS_FINISHED,
            'mark' => 25,
            'threshold' => (float)$this->resourceTask->threshold,
            'is_success' => '',
            'plan' => [],
            'can_execute' => true,
            'attempts_limit' => 0,
            'attempts_count' => 0
        ], $this->resourceTask->toSmallArray());
    }

    private function _toArray($task)
    {
        $arr = [
            'id' => $task->id,
            'site_id' => $task->site_id,
            'parent_id' => $task->parent_id,
            'element_id' => $task->element_id,
            'threshold' => $task->threshold,
            'type' => $task->type,
            'description' => $task->description,
            'title' => $task->title,
            'is_deleted' => $task->is_deleted,
            'created_at' => strtotime($task->created_at).'000',
            'updated_at' => '000',
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'lft' => 0,
            'rgt' => 0,
            'depth' => 0
        ];
        return $arr;
    }

    public function testToArray()
    {
        TaskRefUser::assignUser($this->resourceTask->id, $this->user->id);
        $ref = TaskRefUser::getByUserAndTask($this->resourceTask->id, $this->user->id);
        $ref->status = TaskRefUser::STATUS_FINISHED;
        $ref->mark = 25;
        $ref->save();

        $arr = $this->_toArray($this->resourceTask);
        $arr['status'] = TaskRefUser::STATUS_FINISHED;
        $arr['mark'] = 25;
        $arr['attempts_limit'] = 0;
        $arr['attempts_count'] = 0;
        $arr['plan'] = [];
        $arr['plan_percent_complete'] = 0;
        $arr['plan_sum'] = 0;
        $arr['plan_percent'] = 0;
        $arr['plan_sum_max'] = 0;
        $arr['is_success'] = false;
        $arr['element'] = $this->resource->toArray();
        $arr['start_element'] = null;
        $arr['can_execute'] = true;
        $this->assertEquals($arr, $this->resourceTask->toArray());



        TaskRefUser::assignUser($this->testTask->id, $this->user->id);
        $ref = TaskRefUser::getByUserAndTask($this->testTask->id, $this->user->id);
        $ref->status = TaskRefUser::STATUS_FINISHED;
        $ref->mark = 75;
        $ref->save();

        $arr = $this->_toArray($this->testTask);
        $arr['status'] = TaskRefUser::STATUS_FINISHED;
        $arr['mark'] = 75;
        $arr['attempts_limit'] = 0;
        $arr['attempts_count'] = 0;
        $arr['plan'] = [];
        $arr['plan_percent_complete'] = 0;
        $arr['plan_sum'] = 0;
        $arr['plan_percent'] = 0;
        $arr['plan_sum_max'] = 0;
        $arr['is_success'] = false;
        $arr['element'] = $this->test->toArray();
        $arr['start_element'] = null;
        $arr['can_execute'] = true;
        $this->assertEquals($arr, $this->testTask->toArray());



        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);
        $ref = TaskRefUser::getByUserAndTask($this->courseTask->id, $this->user->id);
        $ref->status = TaskRefUser::STATUS_FINISHED;
        $ref->mark = 80;
        $ref->save();

        $resourceTestTask = Task::getByElement($this->courseTask->id, $this->resource->id, Task::TYPE_RESOURCE);
        //set some mark
        $uRef = TaskRefUser::getByUserAndTask($resourceTestTask->id, $this->user->id);
        $uRef->mark = 100;
        $uRef->status = TaskRefUser::STATUS_FINISHED;
        $uRef->attempts_count = 1;
        $uRef->save();

        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);

        $arr = $this->_toArray($this->courseTask);
        $arr['lft'] = 1;
        $arr['rgt'] = 6;
        $arr['status'] = TaskRefUser::STATUS_FINISHED;
        $arr['mark'] = 80;
        $arr['threshold'] = (float)$this->courseTask->threshold;
        $arr['attempts_count'] = 0;
        $arr['attempts_limit'] = 0;
        $arr['plan'] = [
            $resourceTestTask->toSmallArray(),
            $courseTestTask->toSmallArray()
        ];
        $arr['plan_percent_complete'] = 50;
        $arr['plan_sum'] = 100;
        $arr['plan_percent'] = 50;
        $arr['plan_sum_max'] = 200;
        $arr['is_success'] = true;
        $arr['element'] = $this->course->toArray();
        $arr['start_element'] = null;
        $arr['can_execute'] = true;
        $this->assertEquals($arr, $this->courseTask->toArray());

        //set some mark
        $uRef = TaskRefUser::getByUserAndTask($courseTestTask->id, $this->user->id);
        $uRef->mark = 56;
        $uRef->status = TaskRefUser::STATUS_FINISHED;
        $uRef->attempts_count = 3;
        $uRef->save();

        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertEquals([
            'id' => $courseTestTask->id,
            'type' =>$courseTestTask->type,
            'title' => $courseTestTask->title,
            'status' => TaskRefUser::STATUS_FINISHED,
            'mark' => 56,
            'threshold' => (float)$courseTestTask->threshold,
            'is_success' => 1,
            'plan' => [],
            'can_execute' => false,
            'attempts_limit' => 2,
            'attempts_count' => 3
        ], $courseTestTask->toSmallArray());

        $taskSett = CourseTestSetting::getByCourseId((int)$this->course->id, (int)$this->test->id);
        $taskSett->unlim_attempts = true;
        $taskSett->save();

        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertEquals([
            'id' => $courseTestTask->id,
            'type' =>$courseTestTask->type,
            'title' => $courseTestTask->title,
            'status' => TaskRefUser::STATUS_FINISHED,
            'mark' => 56,
            'threshold' => (float)$courseTestTask->threshold,
            'is_success' => 1,
            'plan' => [],
            'can_execute' => true,
            'attempts_count' => 3,
            'attempts_limit' => 0,
        ], $courseTestTask->toSmallArray());
    }

    public function testGetNextSubTask()
    {
        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);
        $resourceTestTask = Task::getByElement($this->courseTask->id, $this->resource->id, Task::TYPE_RESOURCE);
        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);

        $next = $resourceTestTask->getNextSubTask();
        $this->assertEquals([
            'id' => $courseTestTask->id,
            'type' =>$courseTestTask->type,
            'title' => $courseTestTask->title,
            'status' => '',
            'mark' => 0,
            'threshold' => (float)$courseTestTask->threshold,
            'is_success' => '',
            'plan' => [],
            'can_execute' => true,
            'attempts_count' => 0,
            'attempts_limit' => 2
        ], $next->toSmallArray());


        $next = $courseTestTask->getNextSubTask();
        $this->assertEquals(null, $next);

        $uRef = TaskRefUser::getByUserAndTask($courseTestTask->id, $this->user->id);
        $uRef->mark = 56;
        $uRef->status = TaskRefUser::STATUS_FINISHED;
        $uRef->attempts_count = 3;
        $uRef->save();

        $next = $resourceTestTask->getNextSubTask();
        $this->assertEquals(null, $next);
    }
}