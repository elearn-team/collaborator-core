<?php

namespace Modules\Tasks\Tests\Webservice;

use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Resources\Model\Page;
use Modules\Tests\Model\Test;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseTestSetting;


class TaskResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $resource;
    protected $resourceTask;

    protected $test;
    protected $testTask;

    protected $course;
    protected $courseTask;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->resource = Page::create();
        $this->resource->type = 'page';
        $this->resource->title = 'test page';
        $this->resource->description = 'test page';
        $this->resource->save();
        $this->resource = Page::getById($this->resource->id);
        $this->models [] = $this->resource;

        $this->resourceTask = Task::create();
        $this->resourceTask->element_id = $this->resource->id;
        $this->resourceTask->type = Task::TYPE_RESOURCE;
        $this->resourceTask->threshold = 75;
        $this->resourceTask->title = 'Test task';
        $this->resourceTask->description = 'Test task description';
        $this->resourceTask->save();
        $this->resourceTask = Task::getById($this->resourceTask->id);
        $this->models [] = $this->resourceTask;


        $this->test = Test::create();
        $this->test->title = 'some test 2';
        $this->test->description = 'some test 2';
        $this->test->save();
        $this->test = Test::getById($this->test->id);
        $this->models [] = $this->test;

        $this->testTask = Task::create();
        $this->testTask->element_id = $this->test->id;
        $this->testTask->type = Task::TYPE_TEST;
        $this->testTask->threshold = 75;
        $this->testTask->title = 'Test task';
        $this->testTask->description = 'Test task description';
        $this->testTask->save();
        $this->testTask = Task::getById($this->testTask->id);
        $this->models [] = $this->testTask;


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
        $taskSett->attempts_count = 1;
        $taskSett->save();

        $this->courseTask = Task::create();
        $this->courseTask->element_id = $this->course->id;
        $this->courseTask->type = Task::TYPE_COURSE;
        $this->courseTask->threshold = 75;
        $this->courseTask->title = 'Test task';
        $this->courseTask->description = 'Test task description';
        $this->courseTask->saveForCourse();
        $this->courseTask = Task::getById($this->courseTask->id);
        $this->models [] = $this->courseTask;

    }

    public function testGetItemNotFound()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Task "9999" not found'
        ]);
        $this->assertResponse('GET /tasks/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);
    }

    public function testGetItemAsEditor()
    {
        $taskArr = $this->resourceTask->toArray();

        $this->addPermission('tasks.can_manage_tasks');

        /*$_GET['is_edit'] = true;
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->task->id, [
            'data' => json_encode(array(
                'id' => $this->task->id,
                'is_edit' => true
            ))
        ], $response);
        unset($_GET['is_edit']);

        /*$_GET['mode'] = true;
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->task->id, [
            'data' => json_encode(array(
                'id' => $this->task->id
            ))
        ], $response);
        unset($_GET['mode']);*/


        $response = new \Bazalt\Rest\Response(400, ['not_assign' => 'Task "' . $this->resourceTask->id . '" not assign for user "' . $this->user->id . '"']);
        $this->assertResponse('GET /tasks/' . $this->resourceTask->id, [
            'data' => json_encode(array(
                'id' => $this->resourceTask->id
            ))
        ], $response);

    }

    public function testGetItemAsUser()
    {
        $taskArr = $this->resourceTask->toArray();
        $taskArr['element'] = $this->resource->toArray();

        TaskRefUser::assignUser($this->resourceTask->id, $this->user->id);

        $taskArr['status'] = TaskRefUser::STATUS_IN_PROGRESS;
        $taskArr['mark'] = '';
        $taskArr['attempts_count'] = 1;
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->resourceTask->id, [
            'data' => json_encode(array(
                'id' => $this->resourceTask->id
            ))
        ], $response);

        $userTask = TaskRefUser::getByUserAndTask($this->resourceTask->id, $this->user->id);
        $this->assertEquals(TaskRefUser::STATUS_IN_PROGRESS, $userTask->status);
        $this->assertEquals(1, $userTask->attempts_count);

        $taskArr['status'] = TaskRefUser::STATUS_IN_PROGRESS;
        $taskArr['attempts_count'] = 2;
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->resourceTask->id, [
            'data' => json_encode(array(
                'id' => $this->resourceTask->id
            ))
        ], $response);

        $userTask = TaskRefUser::getByUserAndTask($this->resourceTask->id, $this->user->id);
        $this->assertEquals(2, $userTask->attempts_count);

        //Task::TYPE_TEST:
        $taskArr = $this->testTask->toArray();
        $taskArr['element'] = $this->test->toArray();
        $taskArr['mark'] = 0;
        $taskArr['can_execute'] = true;

        TaskRefUser::assignUser($this->testTask->id, $this->user->id);

        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->testTask->id, [
            'data' => json_encode(array(
                'id' => $this->testTask->id
            ))
        ], $response);

        //Task::TYPE_COURSE:
        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);

        $taskArr = $this->courseTask->toArray();
        $taskArr['element'] = $this->course->toArray();

        $taskArr['status'] = TaskRefUser::STATUS_IN_PROGRESS;
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => $this->courseTask->id
            ))
        ], $response);

        $userTask = TaskRefUser::getByUserAndTask($this->courseTask->id, $this->user->id);
        $this->assertEquals(TaskRefUser::STATUS_IN_PROGRESS, $userTask->status);


        $this->course->start_type = Course::START_TYPE_PLAN;
        $this->course->save();

        $startElement = CoursePlan::getPlanItem($this->resource->id, Task::TYPE_RESOURCE, $this->course->id);
        $startElementTask = Task::getByElement($this->courseTask->id, $this->resource->id, Task::TYPE_RESOURCE);

        $taskArr = $this->courseTask->toArray();
        $taskArr['start_element'] = $startElementTask->toSmallArray();
        $taskArr['element'] = $this->course->toArray();
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => $this->courseTask->id
            ))
        ], $response);

        $testElement = CoursePlan::getPlanItem($this->test->id, Task::TYPE_TEST, $this->course->id);
        $testElementTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $userTask = TaskRefUser::getByUserAndTask($testElementTask->id, $this->user->id);
        $userTask->status = TaskRefUser::STATUS_IN_PROGRESS;
        $userTask->save();

        $taskArr = $this->courseTask->toArray();
        $taskArr['start_element'] = $testElementTask->toSmallArray();
        $taskArr['element'] = $this->course->toArray();
        $response = new \Bazalt\Rest\Response(200, $taskArr);
        $this->assertResponse('GET /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => $this->courseTask->id
            ))
        ], $response);
    }

    public function testAddAttempt()
    {
        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertFalse($courseTestTask->canExecute());

        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);
        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertTrue($courseTestTask->canExecute());

        $uRef = TaskRefUser::getByUserAndTask($courseTestTask->id, $this->user->id);
        $uRef->mark = 100;
        $uRef->status = TaskRefUser::STATUS_FINISHED;
        $uRef->attempts_count = 1;
        $uRef->save();

        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertFalse($courseTestTask->canExecute());


        $_GET['action'] = 'add-attempt';
        $response = new \Bazalt\Rest\Response(400, [
            'id' => [
                'required' => 'Field cannot be empty'
            ],
            'user_id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('PUT /tasks/9999', [
            'data' => json_encode([])
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('PUT /tasks/' . $courseTestTask->id, [
            'data' => json_encode(array(
                'id' => $courseTestTask->id,
                'user_id' => 1
            ))
        ], $response);


        $this->addPermission('tasks.can_manage_tasks');
        $this->addPermission('tests.can_manage_attempts');
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Task "9999" not found'
        ]);
        $this->assertResponse('PUT /tasks/9999', [
            'data' => json_encode(array(
                'id' => '9999',
                'user_id' => 1
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, null);
        $this->assertResponse('PUT /tasks/' . $courseTestTask->id, [
            'data' => json_encode(array(
                'id' => $courseTestTask->id,
                'user_id' => $this->user->id
            ))
        ], $response);

        $courseTestTask = Task::getByElement($this->courseTask->id, $this->test->id, Task::TYPE_TEST);
        $this->assertTrue($courseTestTask->canExecute());
    }

    public function testMarkAsComplete()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => ['required' => 'Field cannot be empty']
        ]);
        $_GET['action'] = 'mark-as-complete';
        $this->assertResponse('PUT /tasks/9999', [
            'data' => json_encode(array(
                'id' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Task "9999" not found'
        ]);
        $_GET['action'] = 'mark-as-complete';
        $this->assertResponse('PUT /tasks/9999', [
            'data' => json_encode(array(
                'id' => 9999
            ))
        ], $response);


        $response = new \Bazalt\Rest\Response(400, [
            'not_assign' => 'Task "' . $this->courseTask->id . '" not assign for user "' . $this->user->id . '"'
        ]);
        $_GET['action'] = 'mark-as-complete';
        $this->assertResponse('PUT /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => 9999
            ))
        ], $response);

        TaskRefUser::assignUser($this->courseTask->id, $this->user->id);

        $response = new \Bazalt\Rest\Response(200, null);
        $_GET['action'] = 'mark-as-complete';
        $this->assertResponse('PUT /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => 9999
            ))
        ], $response);

    }

    public function testSaveItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => ['required' => 'Field cannot be empty'],
            'title' => ['required' => 'Field cannot be empty']
        ]);
        $this->assertResponse('POST /tasks/9999', [
            'data' => json_encode(array())
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Task "9999" not found'
        ]);
        $this->assertResponse('POST /tasks/9999', [
            'data' => json_encode(array(
                'id' => 9999,
                'title' => 'title'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /tasks/' . $this->testTask->id, [
            'data' => json_encode(array(
                'id' => $this->testTask->id,
                'title' => 'title'
            ))
        ], $response);

        $this->addPermission('tasks.can_manage_tasks');

        $this->assertResponseCode('POST /tasks/' . $this->testTask->id, [
            'data' => json_encode(array(
                'id' => $this->testTask->id,
                'title' => 'test_title',
                'type' => Task::TYPE_TEST,
                'description' => 'test description'
            ))
        ], 200);

        $task = Task::getById($this->testTask->id);
        $this->assertEquals('test_title', $task->title);
        $this->assertEquals('test description', $task->description);

    }

    public function testDeleteItem()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('DELETE /tasks/' . $this->testTask->id, ['contentType' => 'application/json'], $response);

        $task = Task::getById($this->testTask->id);
        $this->assertEquals(0, $task->is_deleted);

        $this->addPermission('tasks.can_manage_tasks');

        $response = new \Bazalt\Rest\Response(400, ['id' => 'Task "9999" not found']);
        $this->assertResponse('DELETE /tasks/9999', ['contentType' => 'application/json'], $response);


        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /tasks/' . $this->testTask->id, ['contentType' => 'application/json'], $response);

        $task = Task::getById($this->testTask->id);
        $this->assertEquals(1, $task->is_deleted);
    }

    public function testGetUser()
    {

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'getUsers';
        $this->assertResponse('GET /tasks/' . $this->courseTask->id, [], $response);

        $this->addPermission('tasks.can_manage_tasks');

//        $response = new \Bazalt\Rest\Response(400, ['id' => 'Task "9999" not found']);
//        $_GET['action'] = 'getUsers';
//        $this->assertResponse('GET /tasks/9999', [], $response);

//
//        $_GET['action'] = 'getUsers';
//        $this->assertResponseCode('GET /tasks/'.$this->courseTask->id, [], 200);

    }

    public function testAssignUser()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'assign';
        $this->assertResponse('POST /tasks/9999', [
            'data' => json_encode(array())
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'assign';
        $this->assertResponse('POST /tasks/9999', [
            'data' => json_encode(array())
        ], $response);

        $this->addPermission('tasks.can_manage_tasks');

        $response = new \Bazalt\Rest\Response(403, 'Error occurred');
        $_GET['action'] = 'assign';
        $this->assertResponse('POST /tasks/9999', [
            'data' => json_encode(array())
        ], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => []]);
        $_GET['action'] = 'assign';
        $this->assertResponse('POST /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => $this->user->id,
                'checked' =>  false
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => []]);
        $_GET['action'] = 'assign';
        $this->assertResponse('POST /tasks/' . $this->courseTask->id, [
            'data' => json_encode(array(
                'id' => $this->user->id,
                'checked' =>  $this->user->id
            ))
        ], $response);


    }

}