<?php


namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;
use Modules\Resources\Model\Page;
use Tonic;
class CoursePlanResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $course;
    protected $page;
    protected $plan;

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

        $this->page = Page::create();
        $this->page->title = 'test page';
        $this->page->type = 'page';
        $this->page->template = 'default.html';
        $this->page->save();

        $this->plan = CoursePlan::create($this->course->id);
        $this->plan->element_id = $this->page->id;
        $this->plan->type = $this->page->type;
        $this->plan->order = 1;
        $this->plan->save();

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testDeleteItem(){
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'CourseElement "9999" not found'
        ]);
        $this->assertResponse('DELETE /courses/'.$this->course->id.'/plan/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, true);
        $this->assertResponse('DELETE /courses/'.$this->course->id.'/plan/'.$this->plan->id, ['contentType' => 'application/json'], $response);
        $plan = CoursePlan::getById($this->plan->id);
        $this->assertEquals(null, $plan);

    }

}
 