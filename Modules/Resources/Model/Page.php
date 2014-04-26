<?php

namespace Modules\Resources\Model;

use Bazalt\ORM;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;

//use Framework\Core\Helper\Url;

class Page extends Base\Page //implements \Bazalt\Routing\Sluggable
{
    /**
     * Create new page without saving in database
     */
    public static function create()
    {
        $page = new Page();
        $page->site_id = \Bazalt\Site::getId();
        if (!\Bazalt\Auth::getUser()->isGuest()) {
            $page->user_id = \Bazalt\Auth::getUser()->id;
        }
        return $page;
    }

//    /**
//     * Get page by url
//     */
//    public static function getByUrl($url, $is_published = null, $userId = null)
//    {
//        $q = Page::select()
//            ->where('url = ?', $url)
//            ->andWhere('f.site_id = ?', \Bazalt\Site::getId());
//
//        if ($is_published != null) {
//            $q->andWhere('is_published = ?', $is_published);
//        }
//        if ($userId != null) {
//            $q->andWhere('user_id = ?', $userId);
//        }
//        $q->limit(1);
//        return $q->fetch();
//    }

    public static function searchByTitle($title)
    {
        $q = ORM::select('Modules\\Resources\\Model\\Page p', 'p.*')
            ->where('p.title LIKE ?', $title . "%")
            ->andWhere('p.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('is_published = ?', 1)
            ->groupBy('p.id');

        return new ORM\Collection($q);
    }

    public static function search($type, $title, $categoryId = null, $tag = null)
    {
        $q = ORM::select('Modules\\Resources\\Model\\Page f', 'f.*')
            ->leftJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id ')
            ->leftJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ')
            ->andWhere('f.is_published = ?', 1)
            ->andWhere('f.type = ?', $type)
            ->andWhere('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('(LOWER(f.title) LIKE ? OR LOWER(t.body) LIKE ?)', array('%' . mb_strtolower($title) . '%', '%' . mb_strtolower($title) . '%'));
        if ($categoryId) {
            $q->andWhere('f.category_id = ?', (int)$categoryId);
        }
        if ($tag) {
            $q->andWhere('(LOWER(t.body) LIKE ?)', array('%' . mb_strtolower($tag) . '%'));
        }
        $q->groupBy('f.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public static function deleteByIds($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $q = ORM::delete('Modules\Resources\Model\Page a')
            ->whereIn('a.id', $ids)
            ->andWhere('a.site_id = ?', \Bazalt\Site::getId());

        return $q->exec();
    }

    public static function getCollection($onlyPublished = null, Category $category = null)
    {
        $q = ORM::select('Modules\Resources\Model\Page f', 'f.*')
            ->andWhere('f.site_id = ?', \Bazalt\Site::getId());

        if ($onlyPublished) {
            $q->andWhere('is_published = ?', 1);
        }
        if ($category) {
            $childsQuery = ORM::select('Modules\Resources\Model\Category c', 'id')
                ->where('c.lft BETWEEN ? AND ?', array($category->lft, $category->rgt))
                ->andWhere('c.site_id = ?', $category->site_id);

            $q->andWhereIn('f.category_id', $childsQuery);
        }
        $q->orderBy('created_at DESC')
            ->groupBy('f.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public function toArray()
    {
        $res = parent::toArray();

        $res['is_published'] = $res['is_published'] == '1';
        $res['open_in_window'] = (bool)$res['open_in_window'];

        if ($user = $this->User) {
            $res['user'] = [
                'id' => $user->id,
                'name' => $user->getName()
            ];
        }

        if ($category = $this->Category) {
            $res['breadcrumbs'] = [];
            $path = $this->Category->PublicElements->getPath();
            foreach ($path as $cat) {
                $data = $cat->toArray();
                unset($data['children']);
                $res['breadcrumbs'][] = $data;
            }
            if ($category->is_published && !$category->is_hidden) {
                $data = $category->toArray();
                unset($data['children']);
                $res['breadcrumbs'][] = $data;
            }
        }

        $res['files'] = [];
        $files = $this->Files->get();
//        print_r($files); exit;
        foreach ($files as $file) {
            try {
                $res['files'][] = $file->toArray();
            } catch (\Exception $e) {

            }
        }

        $res['videos'] = [];
        $videos = $this->Videos->get();
        foreach ($videos as $video) {
            try {
                $res['videos'][] = $video->toArray();
            } catch (\Exception $e) {

            }
        }

        $res['tags'] = [];
        $tags = TagRefElement::getElementTags($this->id, Task::TYPE_RESOURCE);
        foreach($tags as $itm){
            $res['tags'] []= $itm->body;
        }

        return $res;
    }
}