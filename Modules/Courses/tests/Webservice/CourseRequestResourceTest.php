<?php

namespace Modules\Courses\tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseRequest;

class CourseRequestResourceTest extends \Bazalt\Auth\Test\BaseCase
{

    protected $course;
    protected $courseRes;
    protected $request;

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
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testDeleteItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course request "9999" not found'
        ]);
        $this->assertResponse('DELETE /courses/' . $this->course->id . '/requests/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /courses/' . $this->course->id . '/requests/' . $this->request->id, ['contentType' => 'application/json'], $response);
        $plan = CourseRequest::getById($this->request->id);
        $this->assertEquals(null, $plan);

    }
}
 