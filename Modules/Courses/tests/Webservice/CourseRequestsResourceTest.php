<?php


namespace Modules\Courses\tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseRequest;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;

class CourseRequestsResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $course;
    protected $courseRes;
    protected $request;
    protected $task;
    protected $res;

    protected function setUp()
    {
        parent::setup();
        $this->initApp(getWebServices());

        $this->course = Course::create();
        $this->course->title = 'Test course';
        $this->course->is_published = 1;
        $this->course->finish_type = 'summary';
        $this->course->start_type = 'start_page';
        $this->course->save();
        $this->courseRes = $this->course->toArray();

        $curUser = \Bazalt\Auth::getUser();

        $this->request = CourseRequest::create();
        $this->request->user_id = $curUser->id;
        $this->request->course_id = $this->course->id;
        $this->request->save();


        $this->task = Task::create();
        $this->task->title = $this->course->title;
        $this->task->type = Task::TYPE_COURSE;
        $this->task->element_id = $this->course->id;
        $this->task->saveForCourse();


        $collection = CourseRequest::getAvailableCourses();
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id');

        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::OK, $table->fetch($_GET));
        }

        $this->res = $table->fetch($_GET, function ($item) use ($curUser) {
            $item['task_id'] = TaskRefUser::getTaskIdByCourse((int)$item['id'], (int)$curUser->id);
            return $item;
        });

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetAvailableCourses()
    {
        $response = new \Bazalt\Rest\Response(200, $this->res);
        $_GET['action'] = 'available-courses';
        $this->assertResponse('GET /courses-requests', ['contentType' => 'application/json'], $response);
    }

    public function testCreateItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'course_id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('PUT /courses-requests', [
            'data' => json_encode(array(
                'course_id' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, $this->task->toArray());

        $this->assertResponse('PUT /courses-requests', [
            'data' => json_encode(array(
                'course_id' => $this->course->id
            ))
        ], $response);
    }
}
 