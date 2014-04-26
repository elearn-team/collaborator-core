<?php


namespace Modules\Tests\Tests\Webservice;

use Bazalt\Rest;
use Modules\Tests\Model\Test;
use Tonic;
use Tonic\Response;

class TestResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $test;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->test = Test::create();
        $this->test->title = 'bla-bla-bla';
        $this->test->save();
        $this->testRes = $this->test->toArray();

        $this->models [] = $this->test;
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetTest()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Test "9999" not found'
        ]);
        $this->assertResponse('GET /tests/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(Response::OK,
            $this->testRes
        );
        $this->assertResponse('GET /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => $this->test->id
            ))
        ], $response);

    }

    public function testSaveTest()
    {

        $response = new \Bazalt\Rest\Response(400, [
            'id' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => '',
                'title' => $this->test->title
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'title' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('POST /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => $this->test->id,
                'title' => ''
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Test "9999" not found'
        ]);
        $this->assertResponse('POST /tests/9999', [
            'data' => json_encode(array(
                'id' => '9999',
                'title' => $this->test->title
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $this->assertResponse('POST /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => $this->test->id,
                'title' => $this->test->title
            ))
        ], $response);

        $this->addPermission('tests.can_manage_tests');


        $response = new \Bazalt\Rest\Response(200,
            $this->testRes
        );
        $this->assertResponse('POST /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => $this->test->id,
                'title' => $this->test->title
            ))
        ], $response);


    }

    public function testDeleteTest()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Test "9999" not found'
        ]);
        $this->assertResponse('DELETE /tests/9999', [
            'data' => json_encode(array(
                'id' => '9999'
            ))
        ], $response);

        $response = new \Bazalt\Rest\Response(200,
            true
        );
        $this->assertResponse('DELETE /tests/' . $this->test->id, [
            'data' => json_encode(array(
                'id' => $this->test->id
            ))
        ], $response);

    }

    public function testDuplicateTest()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Test "9999" not found'
        ]);
        $_GET['action'] = 'duplicateTest';
        $this->assertResponse('POST /tests/9999', [], $response);


        $response = new \Bazalt\Rest\Response(403, 'Permission denied');
        $_GET['action'] = 'duplicateTest';
        $this->assertResponse('POST /tests/' . $this->test->id, [], $response);

        $this->addPermission('tests.can_manage_tests');

        $response = new \Bazalt\Rest\Response(200, true);
        $_GET['action'] = 'duplicateTest';
        $this->assertResponse('POST /tests/' . $this->test->id, [], $response);

    }

    public function testGetReportByResultId()
    {
        $response = new \Bazalt\Rest\Response(400, [
            'resultId' => ['required' => 'Field cannot be empty']
        ]);
        $_GET['action'] = 'getReportByResultId';
        $this->assertResponse('GET /tests/9999', [], $response);


        $response = new \Bazalt\Rest\Response(400, [
            'id' => 'Test "9999" not found'
        ]);
        $_GET['action'] = 'getReportByResultId';
        $_GET['resultId'] = '9999';
        $this->assertResponse('GET /tests/9999', [], $response);

        $response = new \Bazalt\Rest\Response(200, ['data' => []]);
        $_GET['action'] = 'getReportByResultId';
        $_GET['resultId'] = '9999';
        $this->assertResponse('GET /tests/'. $this->test->id, [], $response);
    }

}