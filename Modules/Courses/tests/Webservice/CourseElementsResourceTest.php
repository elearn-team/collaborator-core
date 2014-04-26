<?php

namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;
use Modules\Resources\Model\Page;
use Tonic;
use Tonic\Response;

class CourseElementsResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $course;
    protected $courseRes;
    protected $page;
    protected $page1;
    protected $pageRes;
    protected $courseElement;
    protected $courseElements = [];

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
        $this->page->is_published = 1;
        $this->page->save();

        $this->page1 = Page::create();
        $this->page1->title = 'test page1';
        $this->page1->type = 'page';
        $this->page1->template = 'default.html';
        $this->page1->is_published = 1;
        $this->page1->save();

        $this->pageRes = $this->page->toArray();

        $this->courseElement = CourseElement::create($this->course->id);
        $this->courseElement->element_id = $this->page->id;
        $this->courseElement->type = 'resource';
        $this->courseElement->order = 1;
        $this->courseElement->save();

        $items = CourseElement::getList($this->course->id);
        $res = [];
        foreach ($items as $item) {
            $res [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'type' => $item->type,
                'sub_type' => $item->sub_type,
                'title' => $item->title,
                'code' => $item->code,
                'description' => $item->description
            ];
        }
        $this->courseElements['data'] = $res;

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetList()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('GET /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200, [
            'data' => []
        ]);
        $this->assertResponse('GET /courses/9999/elements', [
            'data' => json_encode(array(
                'id' => 9999
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, $this->courseElements);
        $this->assertResponse('GET /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);
    }

    public function testGetAllResources()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('GET /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200, [
            'data' => []
        ]);
        $_GET['type'] = 'page';
        $this->assertResponse('GET /courses/9999/elements', [
            'data' => json_encode(array(
                'id' => 9999
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200, $this->courseElements);
        $_GET['type'] = 'page';
        $this->assertResponse('GET /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);

    }

    public function testResort()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'resort';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'orders' => [$this->page->id => 3, $this->page1->id => 9]
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'resort';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'orders' => [$this->page->id => 3, $this->page1->id => 9]
            ))
        ], $response);

    }

    public function testCreateItem(){

        $response = new \Bazalt\Rest\Response(400, [
            'id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id.'/elements', [
            'data' => json_encode(array(
                'id' => '',
                'type' => $this->courseElement->type
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'type' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id.'/elements', [
            'data' => json_encode(array(
                'id' => $this->courseElement->id,
                'type' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->courseElement->id,
                'type' =>$this->courseElement->type
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        list($code, $retResponse) = $this->send('POST /courses/' . $this->course->id . '/elements', [
            'data' => json_encode(array(
                'id' => $this->page->id,
                'type' => 'page'
            ))
        ]);
        $this->assertEquals('resource', $retResponse['type']);
    }
}
 