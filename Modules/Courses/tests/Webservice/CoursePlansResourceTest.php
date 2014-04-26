<?php

namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseElement;
use Modules\Resources\Model\Page;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Tonic;
use Tonic\Response;

class CoursePlansResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $course;
    protected $courseRes;
    protected $plan;
    protected $elementsList;
    protected $page;
    protected $page1;
    protected $page2;
    protected $allResources;
    protected $courseElement;
    protected $courseElement1;

    protected $coursePlan;
    protected $coursePlan1;

    protected $courseTask;

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
        $this->models []= $this->course;

        $this->page = Page::create();
        $this->page->title = 'test page';
        $this->page->type = 'page';
        $this->page->template = 'default.html';
        $this->page->is_published = 1;
        $this->page->save();
        $this->models []= $this->page;

        $this->page1 = Page::create();
        $this->page1->title = 'test page1';
        $this->page1->type = 'page';
        $this->page1->template = 'default.html';
        $this->page1->is_published = 1;
        $this->page1->save();
        $this->models []= $this->page1;

        $this->page2 = Page::create();
        $this->page2->title = 'test page2';
        $this->page2->type = 'page';
        $this->page2->template = 'default.html';
        $this->page2->is_published = 1;
        $this->page2->save();
        $this->models []= $this->page2;

        Tag::addTag($this->page2->id, Task::TYPE_RESOURCE, 'test tag');

        $this->courseElement = CourseElement::create($this->course->id);
        $this->courseElement->element_id = $this->page->id;
        $this->courseElement->type = 'page';
        $this->courseElement->save();

        $this->courseElement1 = CourseElement::create($this->course->id);
        $this->courseElement1->element_id = $this->page1->id;
        $this->courseElement1->type = 'page';
        $this->courseElement1->save();

        $this->coursePlan = CoursePlan::create($this->course->id);
        $this->coursePlan->course_id = $this->course->id;
        $this->coursePlan->element_id = $this->page->id;
        $this->coursePlan->type = 'resource';
        $this->coursePlan->save();

        $this->coursePlan1 = CoursePlan::create($this->course->id);
        $this->coursePlan1->course_id = $this->course->id;
        $this->coursePlan1->element_id = $this->page1->id;
        $this->coursePlan1->type = 'resource';
        $this->coursePlan1->save();

        $course = CoursePlan::getList($this->course->id);
        $ret = [];
        foreach ($course as $item) {
            $ret [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'sub_type' => $item->sub_type,
                'type' => $item->type,
                'title' => $item->title,
                'start_element' => $item->start_element,
                'tags' => ''
            ];
        }
        $this->plan['data'] = $ret;

        $items = CoursePlan::getElementsList($this->course->id);
        $res = [];
        foreach ($items as $item) {
            $elementTags = TagRefElement::getElementTags($item->element_id, 'resource');
            $tags = null;
            if(count($elementTags) > 0){
                $tags = implode(',', $elementTags);
            }

            $res [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'type' => $item->type,
                'sub_type' => $item->sub_type,
                'tags' => $tags
            ];
        }

        $this->elementsList['data'] = $res;
        $collection = CoursePlan::searchResource($this->course->id, 'page');
        $collection->limit(10);
        $collection->orderBy('title');
        $allResources = $collection->fetchAll();
        $arr = [];
        foreach ($allResources as $item) {
            $arr [] = [
                'element_id' => $item->id,
                'type' => 'page',
                'element_type' => $item->type,
                'title' => $item->title,
                'tags' => 'test tag'
            ];
        }

        $this->allResources['data'] = $arr;

        $this->courseTask = Task::create();
        $this->courseTask->element_id = $this->course->id;
        $this->courseTask->type = Task::TYPE_COURSE;
        $this->courseTask->threshold = 75;
        $this->courseTask->title = 'Course task';
        $this->courseTask->description = 'Course task description';
        $this->courseTask->saveForCourse();
        $this->courseTask = Task::getById($this->courseTask->id);
        $this->models []= $this->courseTask;
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetList()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(Response::OK,
            $this->plan
        );
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'id' => $this->course->id
            ))
        ], $response);
    }

    public function testGetElementsResource()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'getElementsResource';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(Response::OK,
            $this->elementsList
        );
        $_GET['action'] = 'getElementsResource';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [
            'id' => $this->course->id
        ], $response);
    }

    public function testGetAllResources()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'AllResources';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(400, ['type' => ['required' => 'Field cannot be empty']]);
        $_GET['action'] = 'AllResources';
        $_GET['type'] = '';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);


        $response = new \Bazalt\Rest\Response(200, $this->allResources);
        $_GET['action'] = 'AllResources';
        $_GET['type'] = 'page';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => []]);
        $_GET['action'] = 'AllResources';
        $_GET['type'] = 'page';
        $_GET['title'] = 'bla-bla-bla';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => [0 => [
            'element_id' => $this->page2->id,
            'type' => 'page',
            'element_type' => 'page',
            'title' => $this->page2->title,
            'tags' => 'test tag'
        ]]]);
        $_GET['action'] = 'AllResources';
        $_GET['type'] = 'page';
        $_GET['title'] = 'test page2';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => [0 => [
            'element_id' => $this->page2->id,
            'type' => 'page',
            'element_type' => 'page',
            'title' => $this->page2->title,
            'tags' => 'test tag'
        ]]]);
        $_GET['action'] = 'AllResources';
        $_GET['type'] = 'page';
        $_GET['tag'] = 'test tag';
        $this->assertResponse('GET /courses/' . $this->course->id . '/plan', [], $response);
    }

    public function testCreateItem()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'element_id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'element_id' => '',
                'type' => $this->courseElement->type
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'type' => [
                'required' => 'Field cannot be empty'
            ]
        ]);

        $this->assertResponse('POST /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'element_id' => $this->courseElement->id,
                'type' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');

        $this->assertResponse('POST /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'element_id' => $this->courseElement->id,
                'type' => $this->courseElement->type
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        list($code, $retResponse) = $this->send('POST /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'element_id' => $this->courseElement->id,
                'type' => $this->courseElement->type
            ))
        ]);
        $this->assertEquals('resource', $retResponse['type']);

    }

    public function testResort()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'resort';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'orders' => [$this->coursePlan->id => 3, $this->coursePlan1->id => 9]
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');


        $tasks = $this->courseTask->Elements->get();
        $this->assertEquals($this->coursePlan->element_id, $tasks[0]->element_id);

        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'resort';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'orders' => [$this->coursePlan->id => 2, $this->coursePlan1->id => 1]
            ))
        ], $response);

        $tasks = $this->courseTask->Elements->get();
        $this->assertEquals($this->coursePlan1->element_id, $tasks[0]->element_id);
    }

    public function testChangeStartElement()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'changeStartElement';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'course_id' => $this->course->id,
                'element_id' => $this->courseElement->id
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'changeStartElement';
        $this->assertResponse('PUT /courses/' . $this->course->id . '/plan', [
            'data' => json_encode(array(
                'course_id' => $this->course->id,
                'element_id' => $this->courseElement->id
            ))
        ], $response);

    }

}
 