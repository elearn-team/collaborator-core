<?php

namespace Modules\Resources\Tests\Webservice;

use Bazalt\Rest;
use Modules\Resources\Model\Page;
use Modules\Resources\Model\Category;
use Tonic;

class PagesResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected $pages = [];
    protected $category;

    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());

        for ($i = 0; $i < 3; $i++) {
            $this->_addTestPage($i);
        }
        $user2 = $this->createAdminUser();

        $category = Category::create();
        $category->title = 'Category0';
        $category->url = 'category0';
        $category->save();
        $this->models [] = $category;

        $this->pages[2]->user_id = $user2->id;
        $this->pages[2]->category_id = $category->id;
        $this->pages[2]->save();

        $this->pages[2] = Page::getById($this->pages[2]->id);
        $this->category = $category;
//        exit('O_O');
    }


    private function _addTestPage($i)
    {
        $page = Page::create();

        $page->title = 'page' . $i;
        $page->body = 'page body ' . $i;
        $page->url = 'pg' . $i;
        $page->rating = $i;
        $page->is_published = $i%2 == 0;
        $page->save();
        $this->models [] = $page;
        $this->pages [] = Page::getById((int)$page->id);
    }

    private function _testGetPagesSorting($order, $sorting = array())
    {
        $res = [
            'data' => [],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => count($this->pages),
                'countPerPage' => 10
            ]
        ];
        foreach($order as $i) {
            $res['data'] []= $this->pages[$i]->toArray();
            $res['data'] [$i]['tags'] = array();
        }

        $response = new \Bazalt\Rest\Response(200, $res);
        if(count($sorting) > 0) {
            \Bazalt\Rest\Resource::params(array(
                'sorting' => $sorting
            ));
        }
        $this->assertResponse('GET /pages', [
        ], $response);

    }

    public function testGetPages()
    {
        unset($_GET['q']);
        $_GET['admin'] = true;
        $this->_testGetPagesSorting(array(0, 1, 2), array('title' => 'ASC'));
//        $this->_testGetPagesSorting(array(1, 0, 2), array('user_id' => 'ASC'));
//        $this->_testGetPagesSorting(array(0, 1, 2), array('created_at' => 'ASC'));
//        $this->_testGetPagesSorting(array(1, 2, 0), array('is_published' => 'ASC'));

        $_GET['category_id'] = '999';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::NOTFOUND, 'Category with id "999" not found');
        $this->assertResponse('GET /pages', [
        ], $response);

        $_GET['category_id'] = $this->category->id;
        $page2 = $this->pages[2]->toArray();
        $page2['tags'] = array();
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, [
            'data' => [
                $page2
            ],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ],
            'title' => ''
        ]);
        $this->assertResponse('GET /pages', [
        ], $response);
        unset($_GET['category_id']);

        unset($_GET['admin']);

        $_GET['q'] = 'page0';
        $page0 = $this->pages[0]->toArray();
        $page0['tags'] = array();
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, [
            'data' => [
                $page0
            ],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ]);
        $this->assertResponse('GET /pages', [
        ], $response);

        $_GET['q'] = 'page0';
        $_GET['truncate'] = 10;
        $arr = $this->pages[0]->toArray();
        $arr['tags'] = array();
        $arr['body'] = 'page...';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, [
            'data' => [
                $arr
            ],
            'pager' => [
                'current' => 1,
                'count' => 1,
                'total' => 1,
                'countPerPage' => 10
            ]
        ]);
        $this->assertResponse('GET /pages', [
        ], $response);
    }
}