<?php

namespace Modules\Resources\Tests\Webservice;

use Bazalt\Auth;
use Bazalt\Rest;
use Modules\Resources\Model\Page;
use Tonic\Response;

class PageResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $page;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        $this->page = Page::create();
        $this->page->id = 9999;
        $this->page->save();
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->page && $this->page->id) {
            $this->page->delete();
        }
    }

    public function testGetNotFound()
    {
        // not found
        $response = new \Bazalt\Rest\Response(404, ['id' => 'Page not found']);
        $this->assertResponse('GET /pages/' . 99999, [], $response);
    }

    public function testGetUnpublishedPageByAuthor()
    {
        $response = new \Bazalt\Rest\Response(200, [
            'id' => 9999,
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'category_id' => 0,
            'url' => '',
            'code' => '',
            'description' => '',
            'title' => '',
            'body' => '',
            'template' => 'default.html',
            'is_published' => '',
            'open_in_window' => '',
            'type' => 'page',
            'created_at' => strToTime($this->page->created_at) * 1000,
            'updated_at' => '000',
            'created_by' => 0,
            'updated_by' => $this->user->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->getName()
            ],
            'files' => [],
            'videos' => [],
            'tags' => []
        ]);
        $this->assertResponse('GET /pages/' . $this->page->id, [], $response);
    }

    public function testGetUnpublishedPageByOtherUser()
    {
        $user = \Bazalt\Auth\Model\User::create();
        $user->login = 'Vasya';
        $user->is_active = 1;
        $this->models [] = $user;
        $user->save();
        \Bazalt\Auth::setUser($user);

        $response = new \Bazalt\Rest\Response(Response::FORBIDDEN, ['user_id' => 'This article unpublished']);
        $this->assertResponse('GET /pages/' . $this->page->id, [], $response);
    }

    public function testGetUnpublishedPageByGod()
    {
        $user = \Bazalt\Auth\Model\User::create();
        $user->login = 'Vasya';
        $user->is_active = 1;
        $user->is_god = 1;
        $this->models [] = $user;
        $user->save();
        \Bazalt\Auth::setUser($user);

        $response = new \Bazalt\Rest\Response(200, [
            'id' => 9999,
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'category_id' => 0,
            'url' => '',
            'code' => '',
            'description' => '',
            'title' => '',
            'body' => '',
            'template' => 'default.html',
            'is_published' => '',
            'open_in_window' => '',
            'type' => 'page',
            'created_at' => strToTime($this->page->created_at) * 1000,
            'updated_at' => '000',
            'created_by' => 0,
            'updated_by' => $this->user->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->getName()
            ],
            'files' => [],
            'videos' => [],
            'tags' => []
        ]);
        $this->assertResponse('GET /pages/' . $this->page->id, [], $response);
    }

    public function testPostForbidenForUser()
    {
        $response = new \Bazalt\Rest\Response(Response::FORBIDDEN, 'Permission denied');
        $this->assertResponse('PUT /pages', [], $response);
    }

    public function testPostNotFound()
    {
        $response = new \Bazalt\Rest\Response(Response::NOTFOUND, ['id' => 'Page not found']);
        $this->assertResponse('PUT /pages/99999', [], $response);
    }

    public function testPostValidation()
    {
        $this->addPermission('pages.can_manage_pages');
        $response = new \Bazalt\Rest\Response(Response::BADREQUEST, [
            'title' => [
                'required' => 'Field cannot be empty',
                'length' => [
                    'minlength' => 'String must be more then 1 symbols'
                ]
            ],
            'type' => [
                'required' => 'Field cannot be empty'
            ]
        ]);
        $this->assertResponse('PUT /pages/' . $this->page->id, [], $response);
    }

    public function testPostByGod()
    {
        $user = $this->createAdminUser();
        \Bazalt\Auth::setUser($user);

        list($code, $retResponse) = $this->send('PUT /pages/', [
            'data' => json_encode([
                'title' => 'Test',
                'body' => 'Body',
                'type' => 'page',
                'is_published' => 1,
                'files' => [
                    [
                        'name' => 'test.jpg',
                        'url' => '/test.jpg',
                        'size' => 1024
                    ]
                ]
            ])
        ]);
//        print_r($retResponse);exit;

        $page = Page::getById($retResponse['id']);
        $arr = $page->toArray();
        $arr['template'] = '';
        $response = new \Bazalt\Rest\Response(200, $arr);

        $this->assertEquals($response->code, $code, json_encode($retResponse));
        $this->assertEquals($response->body, $retResponse);
    }

    public function testPostByUser()
    {
        $this->addPermission('pages.can_manage_pages');

        list($code, $retResponse) = $this->send('PUT /pages/', [
            'data' => json_encode([
                'title' => 'Test',
                'is_published' => 1,
                'open_in_window' => 1,
                'type' => 'url',
                'body' => 'Body',
            ])
        ]);

        $response = new \Bazalt\Rest\Response(200, [
            'id' => $retResponse['id'],
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'category_id' => 0,
            'url' => '',
            'code' => '',
            'description' => '',
            'title' => 'Test',
            'body' => 'Body',
            'template' => '',
            'is_published' => '1',
            'open_in_window' => '1',
            'type' => 'url',
            'created_at' => $retResponse['created_at'],
            'updated_at' => $retResponse['updated_at'],
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->getName()
            ],
            'files' => [],
            'videos' => [],
            'tags' => []
        ]);

        $this->assertEquals($response->code, $code, json_encode($retResponse));
        $this->assertEquals($response->body, $retResponse);
    }
}