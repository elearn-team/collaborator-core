<?php

namespace Modules\Resources\Tests\Webservice;

use Bazalt\Rest;
use Modules\Resources\Model\Category;
use Tonic;

class CategoriesResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected  $categories = [];

    protected function setUp()
    {
        parent::setUp();

        \Bazalt\Site\ORM\Localizable::setCurrentSite($this->site);
        \Bazalt\Site\ORM\Localizable::setReturnAllLanguages(false);

        $this->site->addLanguage(\Bazalt\Site\Model\Language::getByAlias('en'));

        $this->initApp(getWebServices());
    }

    private function _addTestCategory($i)
    {
        $category = Category::create();
        $category->title = 'Category' . $i;
        $category->url = 'category' . $i;
        $category->image = 'category_img' . $i;
        $category->save();
        $this->models [] = $category;
        $this->categories [] = Category::getById((int)$category->id);
    }

    public function testGetCategories()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->_addTestCategory($i);
        }

        $_GET['alias'] = 'category10';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::NOTFOUND, 'Category with alias "' . $_GET['alias'] . '" not found');
        $this->assertResponse('GET /pages-categories', [
        ], $response);

        $_GET['alias'] = 'category0';
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK, $this->categories[0]->toArray());
        $this->assertResponse('GET /pages-categories', [
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
        $this->assertResponse('GET /pages-categories', [
        ], $response);

        unset($_GET['q']);
        $response = new \Bazalt\Rest\Response(\Bazalt\Rest\Response::OK,
            $this->categories[0]->toArray()
        );
        $this->assertResponse('GET /pages-categories', [
        ], $response);
    }
}