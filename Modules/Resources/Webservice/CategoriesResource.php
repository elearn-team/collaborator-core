<?php

namespace Modules\Resources\Webservice;

use \Bazalt\Rest\Response,
    \Bazalt\Data as Data;
use Modules\Resources\Model\Category;

/**
 * @uri /pages-categories
 */
class CategoriesResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @provides application/json
     * @json
     * @throws \Exception
     * @return \Bazalt\Rest\Response
     */
    public function getElements()
    {
        if (isset($_GET['alias'])) {
            $category = Category::getByUrl($_GET['alias']);
            if (!$category) {
                return new Response(Response::NOTFOUND, sprintf('Category with alias "%s" not found', $_GET['alias']));
            }
            return new Response(Response::OK, $category->toArray());
        }
        if (isset($_GET['q'])) {
            $collection = Category::searchByTitle($_GET['q']);

            if (!isset($_GET['page'])) {
                $_GET['page'] = 1;
            }
            if (!isset($_GET['count'])) {
                $_GET['count'] = 10;
            }
            $items = $collection->getPage((int)$_GET['page'], (int)$_GET['count']);

            $res = [];
            foreach ($items as $item) {
                $res [] = $item->toArray();
            }
            $data = [
                'data' => $res,
                'pager' => [
                    'current' => $collection->page(),
                    'count' => $collection->getPagesCount(),
                    'total' => $collection->count(),
                    'countPerPage' => $collection->countPerPage()
                ]
            ];
            return new Response(Response::OK, $data);
        }
        $category = Category::getSiteRootCategory();
        if (!$category) {
            throw new \Exception('Root category not found');
        }
        return new Response(200, $category->toArray());
    }

    /**
     * @action getAllCategories
     * @method GET
     * @json
     */
    public function getAllCategories()
    {
        $res = Category::getAllCategories();
        $categories = [];
        if (count($res) > 0) {
            foreach ($res as $item) {
                $categories[] = ['id' => $item->id, 'title' => $item->title];
            }
        }

        return new Response(Response::OK, ['data' => $categories]);
    }
}