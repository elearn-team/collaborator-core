<?php

namespace Modules\Resources\Tests\Webservice;

use Bazalt\Rest;
use Modules\Resources\Model\Category;
use Tonic;

class CategoryResourceTest extends \Bazalt\Auth\Test\BaseCase
{
//    protected  $categories = [];
    protected  $category = null;

    protected function setUp()
    {
        parent::setUp();

        \Bazalt\Site\ORM\Localizable::setCurrentSite($this->site);
        \Bazalt\Site\ORM\Localizable::setReturnAllLanguages(false);

        $this->site->addLanguage(\Bazalt\Site\Model\Language::getByAlias('en'));

        $this->initApp(getWebServices());

        $this->category = Category::create();
        $this->category->title = 'Category';
        $this->category->url = 'category';
        $this->category->image = 'category_img';
        $this->category->save();

        $this->category = Category::getById($this->category->id);

        $this->models [] = $this->category;
    }

    /*private function _addTestCategory($i)
    {
        $category = Category::create();
        $category->title = [
            'en' => 'Category' . $i
        ];
        $category->url = 'category' . $i;
        $category->image = 'category_img' . $i;
        $category->save();
        $this->models [] = $category;
        $this->categories [] = Category::getById((int)$category->id);
    }*/

    public function testGetItem()
    {
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::NOTFOUND, [
            'id' => 'Category with id "999" not found'
        ]);
        $this->assertResponse('GET /pages-categories/999', [
            'id' => '999'
        ], $response);

        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, $this->category->toArray());
        $this->assertResponse('GET /pages-categories/'.$this->category->id, $this->category->toArray(), $response);
    }

    public function testSaveCategory()
    {
        ///$response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, $this->category->toArray());

        /*$dataValidator = Validator::create($this->request->data);
        $category = ($category_id != null) ? Category::getById($category_id) : Category::create();

        if (!$dataValidator->validate()) {
            return new Response(400, $dataValidator->errors());
        }
        $category->title = $dataValidator['title'];
        $category->description = $dataValidator['description'];
        $category->url = $dataValidator['url'];
        $category->is_published = $dataValidator['is_published'] == 'true';
        $category->save();

        return new Response(200, $category->toArray());*/
    }

   /* public function testGetCategories()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->_addTestCategory($i);
        }

        $_GET['alias'] = 'category10';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::NOTFOUND, 'Category with alias "' . $_GET['alias'] . '" not found');
        $this->assertResponse('GET /pages/categories', [
        ], $response);

        $_GET['alias'] = 'category0';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, $this->categories[0]->toArray());
        $this->assertResponse('GET /pages/categories', [
        ], $response);

        unset($_GET['alias']);
        $_GET['q'] = 'Category0';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, [
            'data' => [
                $this->categories[0]->toArray()
            ],
            'pager' => [
                'current'       => 1,
                'count'         => 1,
                'total'         => 1,
                'countPerPage'  => 10
            ]
        ]);
        $this->assertResponse('GET /pages/categories', [
        ], $response);

        unset($_GET['q']);
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK,
            $this->categories[0]->toArray()
        );
        $this->assertResponse('GET /pages/categories', [
        ], $response);
    }*/
}