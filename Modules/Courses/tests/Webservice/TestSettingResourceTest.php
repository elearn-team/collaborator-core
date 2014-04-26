<?php


namespace Modules\Courses\tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseTestSetting;
use Modules\Tests\Model\Test;

class TestSettingResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $test;
    protected $testSettings;
    protected $course;
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

        $this->test = Test::create();
        $this->test->title = 'test test';
        $this->test->save();

        $this->testSettings = CourseTestSetting::create($this->course->id, $this->test->id);
        $this->testSettings->all_questions = true;
        $this->testSettings->unlim_attempts = true;
        $this->testSettings->time_type = 10;
        $this->testSettings->threshold = 12;
        $this->testSettings->training = 1;
        $this->testSettings->save();

        $this->res = [
            'test' => $this->test->toArray(),
            'setting' => $this->testSettings->toArray()
        ];
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetItem()
    {
        $response = new \Bazalt\Rest\Response(200, $this->res);
        $this->assertResponse('GET /courses/' . $this->course->id . '/test/' . $this->test->id, [], $response);
    }

    public function testSaveItem()
    {
        $response = new \Bazalt\Rest\Response(200, $this->testSettings->toArray());

        $this->assertResponse('POST /courses/' . $this->course->id . '/test/' . $this->test->id, [
            'data' => json_encode(array(
                'all_questions' => true,
                'questions_count' => '',
                'unlim_attempts' => true,
                'attempts_count' => '',
                'time_type' => 10,
                'threshold' => 12,
                'training' => 1
            ))
        ], $response);
    }
}
 