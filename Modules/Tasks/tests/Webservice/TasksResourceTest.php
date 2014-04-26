<?php

namespace Modules\Tasks\Tests\Webservice;

use Bazalt\Rest;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tasks\Webservice\TaskSettingResource;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Result;
use Modules\Tests\Model\ResultRefAnswer;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Answer;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Tonic;
use Tonic\Response;
use Bazalt\Auth\Model\User;
use Modules\Tasks\Model\TaskTestSetting;

class TasksResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->initApp(getWebServices());





    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetMyList(){
//        $response = new \Bazalt\Rest\Response(
//            Response::FORBIDDEN, 'Permission denied'
//        );
//        $this->assertResponse('GET /tasks', [], $response);
//
//        $this->addPermission('tests.can_manage_tests');
//
//        $response = new \Bazalt\Rest\Response(Response::OK, null);
//        $this->assertResponse('GET /tests', [], $response);
    }


}