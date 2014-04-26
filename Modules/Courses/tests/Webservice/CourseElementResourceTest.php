<?php


namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;
use Modules\Resources\Model\Page;
use Tonic;

class CourseElementResourceTest extends \Bazalt\Auth\Test\BaseCase
{

    protected $course;
    protected $courseRes;
    protected $page;
    protected $pageRes;
    protected $courseElement;
    protected $courseElementRes;

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

        $this->pageRes = $this->page->toArray();

        $this->courseElement = CourseElement::create($this->course->id);
        $this->courseElement->element_id = $this->page->id;
        $this->courseElement->type = 'page';
        $this->courseElement->save();

        $this->courseElementRes = $this->courseElement->toArray();

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Course element "9999" not found'
        ]);
        $this->assertResponse('GET /courses/9999/elements/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, $this->pageRes);
        $this->assertResponse('GET /courses/'.$this->course->id.'/elements/'. $this->courseElement->id, [], $response);
    }

    public function testDeleteItem(){
        $response = new \Bazalt\Rest\Response(200, $this->pageRes);
        $this->assertResponse('GET /courses/'.$this->course->id.'/elements/'. $this->courseElement->id, [], $response);
    }


}
 