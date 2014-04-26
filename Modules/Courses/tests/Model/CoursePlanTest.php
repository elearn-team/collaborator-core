<?php

namespace Modules\Courses\Tests\CoursePlan;

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

class CoursePlanTest extends \PHPUnit_Framework_TestCase
{
    protected $resource;
    protected $test;

    protected $course;
    protected $courseTask;

    protected $user = null;
    protected $models = [];

    public function setUp()
    {
        $this->user = new User();
        $this->user->login = 'test';
        $this->user->email = 'test@test.test';
        $this->user->is_active = 1;
        $this->user->save();
        $this->models [] = $this->user;
        \Bazalt\Auth::setUser($this->user);

        $this->resource = Page::create();
        $this->resource->type = 'page';
        $this->resource->title = 'test page';
        $this->resource->description = 'test page';
        $this->resource->save();
        $this->resource = Page::getById($this->resource->id);
        $this->models [] = $this->resource;


        $this->test = Test::create();
        $this->test->title = 'some test 0';
        $this->test->description = 'some test 0';
        $this->test->save();
        $this->test = Test::getById($this->test->id);
        $this->models [] = $this->test;


        $this->course = Course::create();
        $this->course->title = 'some course';
        $this->course->description = 'some course';
        $this->course->save();
        $this->course = Course::getById($this->course->id);
        $this->models [] = $this->course;

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
        $this->models [] = $this->courseTask;

        parent::setUp();
    }

    public function tearDown()
    {
        foreach ($this->models as $o) {
            $o->delete();
        }

        parent::tearDown();
    }

    public function testSaveAndDelete()
    {
        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);

        $resource2 = Page::create();
        $resource2->type = 'page';
        $resource2->title = 'test page2';
        $resource2->description = 'test page2';
        $resource2->save();
        $resource2 = Page::getById($resource2->id);
        $this->models [] = $resource2;

        $courseEl = CourseElement::create($this->course->id);
        $courseEl->element_id = (int)$resource2->id;
        $courseEl->type = Task::TYPE_RESOURCE;
        $courseEl->order = 3;
        $courseEl->save();

        $plan = CoursePlan::create($this->course->id);
        $plan->element_id = (int)$resource2->id;
        $plan->type = Task::TYPE_RESOURCE;
        $plan->order = 3;
        $plan->save();

        $tasks = $this->courseTask->Elements->get();
        $this->assertEquals($resource2->id, $tasks[2]->element_id);


        $ref = TaskRefUser::getByUserAndTask($tasks[2]->id, $this->user->id);
        $this->assertTrue($ref != null);

        $plan->delete();
        $this->assertEquals(2, $this->courseTask->Elements->count());
    }

    public function testSaveFinished()
    {
        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);
        $ref = TaskRefUser::getByUserAndTask($this->courseTask->id, $this->user->id);
        $ref->mark = 52;
        $ref->status = TaskRefUser::STATUS_FINISHED;
        $ref->save();

        $resource2 = Page::create();
        $resource2->type = 'page';
        $resource2->title = 'test page2';
        $resource2->description = 'test page2';
        $resource2->save();
        $resource2 = Page::getById($resource2->id);

        $courseEl = CourseElement::create($this->course->id);
        $courseEl->element_id = (int)$resource2->id;
        $courseEl->type = Task::TYPE_RESOURCE;
        $courseEl->order = 3;
        $courseEl->save();

        $plan = CoursePlan::create($this->course->id);
        $plan->element_id = (int)$resource2->id;
        $plan->type = Task::TYPE_RESOURCE;
        $plan->order = 3;
        $plan->save();

        $tasks = $this->courseTask->Elements->get();
        $this->assertEquals($resource2->id, $tasks[2]->element_id);

        $ref = TaskRefUser::getByUserAndTask($tasks[2]->id, $this->user->id);
        $this->assertTrue($ref == null);

        $plan->delete();
        $this->assertEquals(2, $this->courseTask->Elements->count());
    }
}