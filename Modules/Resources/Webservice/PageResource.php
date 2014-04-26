<?php

namespace Modules\Resources\Webservice;

use Bazalt\Data\Validator;
use Bazalt\Rest\Response;
use Modules\Resources\Model\File;
use Modules\Resources\Model\Video;
use Modules\Resources\Model\Page;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;

/**
 * PageResource
 *
 * @uri /pages/:id
 */
class PageResource extends \Bazalt\Rest\Resource
{
    protected function uniqueCookie($cookie, $time = null)
    {
        if (!$time) {
            $time = 60 * 60 * 24;
        }
        $isSet = isset($_COOKIE[$cookie]);
        if (!$isSet) {
            $_COOKIE[$cookie] = true;
            setcookie($cookie, true, time() + $time, '/');
        }
        return !$isSet;
    }

    /**
     * @method GET
     * @json
     */
    public function getItem($id)
    {
        $item = Page::getById($id);
        if (!$item) {
            return new Response(404, ['id' => 'Page not found']);
        }
        $user = \Bazalt\Auth::getUser();
        if (!$item->is_published) {
            if ($user->id != $item->user_id && !$user->hasPermission('pages.can_manage_pages')) {
                return new Response(Response::FORBIDDEN, ['user_id' => 'This article unpublished']);
            }
        }
        return new Response(Response::OK, $item->toArray());
    }

    /**
     * @method PUT
     * @json
     */
    public function saveItem($id = null)
    {
        $dataValidator = \Bazalt\Site\Data\Validator::create($this->request->data);
        $item = ($id == null) ? Page::create() : Page::getById($id);
        if (!$item) {
            return new Response(Response::NOTFOUND, ['id' => 'Page not found']);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('pages.can_manage_pages')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $dataValidator
            ->field('title')
            ->required()
            ->length(1, 255);

        $dataValidator
            ->field('type')
            ->required();

        //$dataValidator->field('is_published')->bool();

        if (!$dataValidator->validate()) {
            return new Response(Response::BADREQUEST, $dataValidator->errors());
        }
        $item->title = $dataValidator['title'];
        $item->body = $dataValidator['body'];
        $item->type = $dataValidator['type'];
        $item->code = $dataValidator['code'];
        $item->description = $dataValidator['description'];

        if (isset($dataValidator['url']) && $dataValidator['url'] && $dataValidator['url'] != 'http://') {
            $item->url = File::parseUrl($dataValidator['url']);
        }

        $item->open_in_window = false;
        if ($dataValidator['type'] === 'html' || $dataValidator['type'] === 'url') {
            $item->open_in_window = (bool)$dataValidator['open_in_window'];
        }

        $item->is_published = $dataValidator['is_published'];
        $item->category_id = isset($dataValidator['category_id']) && (int)$dataValidator['category_id'] ? (int)$dataValidator['category_id'] : null;
        $item->is_top = $dataValidator['is_top'];
        $item->save();
        $ids = [];
        $i = 0;

        TagRefElement::clearTags($item->id, Task::TYPE_RESOURCE);
        if (isset($dataValidator['tags'])) {
            foreach ($dataValidator['tags'] as $itm) {
                Tag::addTag($item->id, Task::TYPE_RESOURCE, $itm);
            }
        }

        if ($dataValidator['files']) {
            if ($dataValidator['type'] === 'page') {

                foreach ($dataValidator['files'] as $data) {
                    $fileArr = (array)$data;
                    if (isset($fileArr['error'])) {
                        continue;
                    }
                    $file = isset($fileArr['id']) ? File::getById((int)$fileArr['id']) : File::create();

                    $file->name = $fileArr['name'];
                    $file->extension = $fileArr['extension'];
                    $file->width = (int)$fileArr['width'];
                    $file->height = (int)$fileArr['height'];

                    $file->url = $fileArr['url'];
                    $file->sort_order = $i;
                    $item->Files->add($file);
                    $ids [] = $file->id;
                }

                $item->Files->clearRelations($ids);

            } elseif ($dataValidator['type'] === 'file') {

                $item->Files->removeAll();

                if (is_array($dataValidator['files'])) {
                    if (count($dataValidator['files']) > 0) {
                        foreach ($dataValidator['files'] as $itm) {
                            $file = File::create();

                            $file->name = $itm->name;
                            $file->extension = $itm->extension;
                            $file->width = (int)$itm->width;
                            $file->height = (int)$itm->height;

                            $file->url = $itm->url;
                            $item->Files->add($file);
                        }
                    }
                } else {

                    $file = File::create();
                    $file->name = $dataValidator['files']->name;
                    $file->extension = $dataValidator['files']->extension;
                    $file->width = (int)$dataValidator['files']->width;
                    $file->height = (int)$dataValidator['files']->height;

                    $file->url = $dataValidator['files']->url;
                    $item->Files->add($file);
                }

            } elseif ($dataValidator['type'] === 'html') {
                if (!isset($dataValidator['files']->id)) {
                    $file = $item->Files->get();
                    if ($file && file_exists(PUBLIC_DIR . $file[0]->url)) {
//                        File::removeDirectory(PUBLIC_DIR . $file[0]->url);
                    }
                    $item->Files->removeAll();
                    $file = File::create();
                } else {
                    $file = File::getById((int)$dataValidator['files']->id);
                }

                $file->name = $dataValidator['files']->name;
                $file->extension = $dataValidator['files']->extension;

                $file->index_page = $dataValidator['files']->index_page;

                $file->url = $dataValidator['files']->url;
                $item->Files->add($file);
            }
        } else {
            if ($dataValidator['type'] === 'html') {
                $file = $item->Files->get();

                if ($file) {
                    $name = explode(".", $file[0]->name);
                    unset($name[count($name) - 1]);
                    $name = implode('.', $name);
                    File::removeDirectory(UPLOAD_DIR . '/multi-resources/' . $name);
                }
            }
            $item->Files->removeAll();


        }


        $ids = [];
        $i = 0;
        if ($dataValidator['videos']) {
            foreach ($dataValidator['videos'] as $data) {
                $video = (array)$data;
                if (empty($video['url'])) {
                    continue;
                }
                $vid = isset($video['id']) ? Video::getById((int)$video['id']) : Video::create();

                $vid->url = $video['url'];
                $vid->sort_order = $i;

                $item->Videos->add($vid);
                $ids [] = $vid->id;
            }
            $item->Videos->clearRelations($ids);
        }
        return new Response(Response::OK, $item->toArray());
    }


    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public
    function deleteItem($id)
    {
        $item = Page::getById((int)$id);
        if (!$item) {
            return new Response(400, ['id' => "Page not found"]);
        }
        $item->delete();
        return new Response(200, true);
    }

    /**
     * @method GET
     * @action getStartFiles
     * @json
     */
    public static function getStartFilesForHtmlElement($id)
    {
        $page = Page::getById($id);
        $files = $page->Files->get();
        if ($files) {
            $name = explode(".", $files[0]->name);
            unset($name[count($name) - 1]);
            $name = implode('.', $name);

            $start_files = array();
            if (is_dir(UPLOAD_DIR . '/multi-resources/' . $name)) {
                if ($dh = opendir(UPLOAD_DIR . '/multi-resources/' . $name)) {
                    while (($file = readdir($dh)) !== false) {


                        if (fnmatch('*.htm', $file) || fnmatch('*.html', $file) || fnmatch('*.swf', $file)) {
                            $start_files[] = $file;
                        }

                    }
                    closedir($dh);
                }
            }
            return new Response(Response::OK, array('start_files' => $start_files));
        }
    }


    /**
     * @method POST
     * @action duplicateResource
     * @json
     */
    public function duplicateResource($id)
    {
        $page = Page::getById($id);
        if (!$page) {
            return new Response(Response::NOTFOUND, ['id' => 'Page not found']);
        }

        $files = $page->Files->get();

        $newPage = new Page();
        $newPage->site_id = $page->site_id;
        $newPage->user_id = $page->user_id;
        $newPage->category_id = ($page->user_id) ? $page->category_id : null;
        $newPage->title = $page->title . ' (Копия)';
        $newPage->code = $page->code;
        $newPage->description = $page->description;
        $newPage->body = $page->body;
        $newPage->url = ($page->url) ? $page->url : null;
        $newPage->is_published = $page->is_published;
        $newPage->open_in_window = $page->open_in_window;
        $newPage->type = $page->type;
        $newPage->save();

        if (count($files) > 0) {
            foreach ($files as $file) {
                $newfile = File::create();
                $newfile->page_id = $newPage->id;
                $newfile->title = $file->title;
                $newfile->name = $file->name;
                $newfile->url = $file->url;
                $newfile->extension = $file->extension;
                $newfile->index_page = $file->index_page;
                $newfile->size = $file->size;
                $newfile->width = $file->width;
                $newfile->height = $file->height;
                $newfile->sort_order = $file->sort_order;
                $newPage->Files->add($newfile);
            }
        }

        $tags = TagRefElement::getElementTags($page->id, Task::TYPE_RESOURCE);
        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                Tag::addTag($newPage->id, Task::TYPE_RESOURCE, $tag->body);
            }
        }

        return new Response(200, true);
    }

}
