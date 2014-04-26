<?php

namespace Modules\Courses\tests\Webservice;

use Bazalt\Rest;
use Modules\Courses\Model\Course;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Tonic;
use Tonic\Response;

class CoursesResourceTest extends \Bazalt\Auth\Test\BaseCase
{

    protected $course1;
    protected $course2;
    protected $courses;
    protected $coursesRes;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->course1 = Course::create();
        $this->course1->title = 'Test course';
        $this->course1->is_published = 1;
        $this->course1->finish_type = 'summary';
        $this->course1->start_type = 'start_page';
        $this->course1->save();

        $this->course2 = Course::create();
        $this->course2->title = 'Test course2';
        $this->course2->is_published = 1;
        $this->course2->finish_type = 'summary';
        $this->course2->start_type = 'start_page';
        $this->course1->save();

        $collection = Course::getCollection();
        $this->courses = new \Bazalt\Rest\Collection($collection);
        $this->coursesRes = $this->courses->fetch($_GET, function($item){
            $tags = TagRefElement::getElementTags($item['id'], Task::TYPE_COURSE);
            $arr = [];
            foreach($tags as $itm){
                $arr[] = $itm->body;
            }

            $item['tags'] = implode(', ', $arr);
            return $item;
        });

    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetList()
    {

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('GET /courses/', [], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(Response::OK,
            $this->coursesRes
        );
        $this->assertResponse('GET /courses', [], $response);
    }

    public function testDeleteMulti()
    {
        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'deleteMulti';
        $this->assertResponse('POST /courses', [
            'data' => json_encode(array(
                'ids' => [998, 999]
            ))
        ], $response);

        $this->addPermission('courses.can_manage_courses');

        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'deleteMulti';
        $this->assertResponse('POST /courses', [
            'data' => json_encode([
                'ids' => [$this->course1->id, $this->course2->id]
            ])
        ], $response);

    }


}
 