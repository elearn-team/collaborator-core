<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\Category;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;

/**
 * CoursesResource
 *
 * @uri /courses
 */
class CoursesResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getList()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $category = null;
        if (isset($_GET['category_id'])) {
            $category = Category::getById((int)$_GET['category_id']);
            if (!$category) {
                return new Response(Response::NOTFOUND,
                    sprintf('Category with id "%s" not found', $_GET['category_id'])
                );
            }
            $collection = Course::getCollection($category);
        } else {
            $collection = Course::getCollection();
        }

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('title')
            ->sortableBy('created_at')

            ->filterBy('description', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(c.description) LIKE ?', $value);
                }
            })
            ->filterBy('title', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(c.title) LIKE ?', $value);
                }
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {

                $tags = $params = $this->params();

                if (isset($tags['tags']) && count($tags['tags']) > 0) {
                    Tag::filterByTags($collection, $tags['tags'], 'course', 'c');
                } else {

                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = c.id AND te.type = \'course\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ');
                    $collection->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(c.created_at) BETWEEN ? AND ?', $params['created_at']);
            });

        $res = $table->fetch($this->params(), function ($item) {
            if (isset($item['description'])) {
                $item['description'] = truncate($item['description'], 302);
            }
            $item['tags'] = implode(', ', $item['tags']);
            return $item;
        });
        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function createItem()
    {
        $res = new CourseResource($this->app, $this->request);
        return $res->saveItem();
    }

    /**
     * @method POST
     * @action deleteMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteMulti()
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        foreach ($data['ids'] as $item) {
            $item = Course::getById((int)$item);
            if ($item) {
                $item->is_deleted = true;
                $item->save();
            }
        }

        return new Response(200, true);
    }

    /**
     * @action upload
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function uploadImage()
    {
        $data = Validator::create($_GET);

        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif'], 2 * 1024 * 1024); //2M
        $result = $uploader->handleUpload(UPLOAD_DIR, ['courses']);

        $result['file'] = '/uploads' . $result['file'];
        $result['url'] = $result['file'];

        if (isset($data['icon'])) {
            return new Response(Response::OK, $result['file']);
        } else {
            return new Response(Response::OK, $result);
        }
    }

    /**
     * @method POST
     * @action changeCategoryMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function changeCategoryMulti()
    {
        $data = Validator::create((array)$this->request->data);
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        if (count($data['ids']) > 0) {
            if ($data['categoryId']) {
                foreach ($data['ids'] as $id) {
                    $item = Course::getById((int)$id);
                    if ($item) {
                        $item->category_id = $data['categoryId'];
                        $item->save();
                    }
                }
            } else {
                foreach ($data['ids'] as $id) {
                    $item = Course::getById((int)$id);
                    if ($item) {
                        $item->category_id = null;
                        $item->save();
                    }
                }
            }

        }


        return new Response(200, true);
    }
}
