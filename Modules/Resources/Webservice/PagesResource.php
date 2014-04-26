<?php

namespace Modules\Resources\Webservice;

use Bazalt\Rest\Response;
use Bazalt\Data\Validator;
use Modules\Resources\Model\Category;
use Modules\Resources\Model\Page;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;

require_once SITE_DIR . '/helpers/truncate.php';

/**
 * PagesResource
 *
 * @uri /pages
 */
class PagesResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItems()
    {
        if (isset($_GET['q'])) {
            $collection = Page::searchByTitle($_GET['q']);
        } else {
            $category = null;
            if (isset($_GET['category_id'])) {
                $category = Category::getById((int)$_GET['category_id']);
                if (!$category) {
                    return new Response(Response::NOTFOUND,
                        sprintf('Category with id "%s" not found', $_GET['category_id'])
                    );
                }
            }

            $user = \Bazalt\Auth::getUser();
            if ($user->isGuest() && isset($_GET['admin'])) {
                return new \Bazalt\Rest\Response(403, 'Access denied');
            }
            $collection = Page::getCollection(($user->isGuest() || !isset($_GET['admin'])), $category);
        }

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table->sortableBy('title')
            ->sortableBy('code')
            ->filterBy('title', function ($collection, $columnName, $value) {
                $collection->andWhere('`' . $columnName . '` LIKE ?', '%' . $value . '%');
            })
            ->filterBy('code', function ($collection, $columnName, $value) {
                $collection->andWhere('`' . $columnName . '` LIKE ?', '%' . $value . '%');
            })
            ->filterBy('tags', function ($collection, $columnName, $value) {

                $tags = $params = $this->params();

                if (isset($tags['tags']) && count($tags['tags']) > 0) {
                    Tag::filterByTags($collection, $tags['tags'], 'resource');
                } else {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id AND te.type = \'resource\' ');
                    $collection->innerJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ');
                    $collection->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($value) . '%');
                }


            })
            ->filterBy('type', function ($collection, $columnName, $value) {
                $collection->andWhere('f.type = ?', $value);
            })
            ->filterBy('is_published', function ($collection, $columnName, $value) {
                if ($value === 'published') {
                    $collection->andWhere('is_published = ?', 1);
                } elseif ($value === 'notPublished') {
                    $collection->andWhere('is_published = ?', 0);
                }

            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                $params = $this->params();
                $collection->andWhere('DATE(f.created_at) BETWEEN ? AND ?', array($params['created_at'][0], $params['created_at'][1]));
            })
            ->sortableBy('user_id')->filterBy('user_id')
            ->sortableBy('type')
            ->sortableBy('created_at')
            ->sortableBy('is_published');
//        echo $collection->toSql();exit;
//        $user = \Bazalt\Auth::getUser();
//        if (isset($_GET['admin']) && !$user->isGuest() && $user->hasPermission('pages.can_manage_pages')) {
//            $collection->andWhere('user_id = ?', $user->id);
//        }

        $res = $table->fetch($this->params(), function ($item) {
            if (isset($_GET['truncate']) && isset($item['body'])) {
                $item['body'] = truncate($item['body'], (int)$_GET['truncate']);
            }
            return $item;
        });

        if (isset($_GET['category_id'])) {
            $parentElements = Category::getParentElements($_GET['category_id']);
            unset($parentElements[0]);
            $titles = array();
            foreach ($parentElements as $item) {
                $titles[] = $item->title;
            }
            $res['title'] = implode(' / ', $titles);
        }

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @json
     */
    public function saveArticle()
    {
        $res = new PageResource($this->app, $this->request);

        return $res->saveItem();
    }


    /**
     * @action upload
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function uploadFiles()
    {

        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif', 'xls', 'xlsx', 'doc', 'docx',
                                               'zip', 'rar', '7z', 'pdf', 'mp4', 'swf', 'flv'], 100 * 1024 * 1024); //100M
        $result = $uploader->handleUpload(UPLOAD_DIR, ['resources']);
        $imageInfo = getimagesize(UPLOAD_DIR . $result['file']);

        $file = explode(".", $result['file']);
        $extension = end($file);

        $result['file'] = '/uploads' . $result['file'];
        $result['url'] = $result['file'];
        $result['extension'] = $extension;
        $result['width'] = $imageInfo[0];
        $result['height'] = $imageInfo[1];


        return new Response(Response::OK, $result);
    }

    /**
     * @action uploadZip
     * @method POST
     * @accepts multipart/form-data
     * @json
     */
    public function uploadZipFiles()
    {
        $uploader = new \Bazalt\Rest\Uploader(['zip'], 100 * 1024 * 1024); //100M
        $result = $uploader->handleUpload(UPLOAD_DIR, ['resources']);

        $file = explode(".", $result['file']);
        $extension = end($file);
//        $name = explode(".", $result['name']);
//        unset($name[count($name) - 1]);
//        $name = implode('.', $name);
        $name = uniqid();

        $zip = new \ZipArchive();
        if ($zip->open(UPLOAD_DIR . $result['file']) === true) {
            $zip->extractTo(UPLOAD_DIR . '/multi-resources/' . $name);
            $zip->close();
        } else {
            return new Response(Response::NOTFOUND,
                sprintf('Unable to unpack ZIP "%s"', $result['file'])
            );
        }

        $files = array();
        $start_files = array();
        if (is_dir(UPLOAD_DIR . '/multi-resources/' . $name)) {
            if ($dh = opendir(UPLOAD_DIR . '/multi-resources/' . $name)) {
                while (($file = readdir($dh)) !== false) {

                    if (fnmatch('index.*', $file)) {
                        $files[] = $file;
                    }

                    if (fnmatch('*.htm', $file) || fnmatch('*.html', $file) || fnmatch('*.swf', $file)) {
                        $start_files[] = $file;
                    }

                }
                closedir($dh);
            }
        }
        if ($start_files) {
            $result['start_files'] = $start_files;
        }
        $result['file'] = '/uploads' . $result['file'];
        $result['url'] = '/uploads/multi-resources/' . $name;
        $result['extension'] = $extension;

        return new Response(Response::OK, $result);
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
            $item = Page::getById((int)$item);
            if ($item) {
                $item->delete();
            }
        }

        return new Response(200, true);
    }
}