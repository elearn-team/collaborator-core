<?php


namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Tonic;
use Bazalt\Auth\Model\User;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Model\Task;

class CourseResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $course;
    protected $courseRes;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->course = Course::create();
        $this->course->title = 'Test course';
        $this->course->is_published = 1;
        $this->course->finish_type = 'summary';
        $this->course->start_type = 'start_page';

        $this->course->save();

        $this->courseRes = $this->course->toArray();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course "9999" not found'
        ]);
        $this->assertResponse('GET /courses/9999', [
            'data' => array(
                'id' => '9999'
            )
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => $this->course->id,
                'title' => $this->course->title
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200,
            $this->courseRes
        );
        $this->assertResponse('GET /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);

    }

    public function testGetReport()
    {
//        $response = new \Bazalt\Rest\Response(400, [
//            'id' => 'Course "9999" not found'
//        ]);
//        $this->assertResponse('GET /courses/9999', [
//            'data' => json_encode(array(
//                'id' => '9999'
//            ))
//        ], $response);
    }

    public function testSaveItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => '',
                'title' => $this->course->title
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => $this->course->id,
                'title' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course "9999" not found'
        ]);
        $this->assertResponse('POST /courses/9999', [
            'data' => json_encode(array(
                'id' => '9999',
                'title' => $this->course->title
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => $this->course->id,
                'title' => $this->course->title
            ))
        ], $response);


        $this->addPermission('courses.can_manage_courses');

        $arr = $this->courseRes;
        $arr['is_published'] = false;
        $response = new \Bazalt\Rest\Response(200, $arr);
        $this->assertResponse('POST /courses/' . $this->course->id, [
            'data' => json_encode(array(
                'id' => $this->course->id,
                'title' => $this->course->title,
                'finish_type' => $this->course->finish_type,
                'start_type' => $this->course->start_type
            ))
        ], $response);

    }

    public function testDeleteTest()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course "9999" not found'
        ]);
        $this->assertResponse('DELETE /courses/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /courses/' . $this->course->id, ['contentType' => 'application/json'], $response);
        $course = Course::getById($this->course->id);
        $this->assertEquals(1, $course->is_deleted);

    }

    public function testGetAssignUsers()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('GET /courses/9999', [
            'data' => array(
                'id' => '9999',
                'action' => 'getAssignUsers'
            )
        ], $response);

        $this->addPermission('courses.can_manage_courses');
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course "9999" not found'
        ]);
        $this->assertResponse('GET /courses/9999', [
            'data' => array(
                'id' => '9999',
                'action' => 'getAssignUsers'
            )
        ], $response);


        $courseTask = Task::create();
        $courseTask->element_id = $this->course->id;
        $courseTask->type = Task::TYPE_COURSE;
        $courseTask->threshold = 75;
        $courseTask->title = 'Course task';
        $courseTask->description = 'Course task description';
        $courseTask->saveForCourse();
        $courseTask = Task::getById($courseTask->id);
        $this->models [] = $courseTask;

        TaskRefUser::assignUser($courseTask->id, $this->user->id);
        $this->user = User::getById($this->user->id);
        $arr = $this->user->toArray();
        $arr['photo_thumb'] = '';
        $arr['tags'] = '';

        $response = new \Bazalt\Rest\Response(200, [
            'data' => [
                $arr
            ],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ]);
        $this->assertResponse('GET /courses/' . $this->course->id, [
            'data' => array(
                'id' => $this->course->id,
                'action' => 'getAssignUsers'
            )
        ], $response);
    }

}
 