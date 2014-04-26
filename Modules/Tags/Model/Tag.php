<?php
namespace Modules\Tags\Model;

use Bazalt\ORM;

class Tag extends Base\Tag
{
    public static function create()
    {
        $o = new Tag();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public static function addTag($elementId, $type, $tagText)
    {
        $tag = self::get($tagText);
        if (!$tag) {
            $tag = self::create();
            $tag->body = $tagText;
            $tag->save();
        }
        if (!TagRefElement::hasTag($elementId, $type, $tag->id)) {
            TagRefElement::addTag($elementId, $type, $tag->id);
        }
    }

    public static function clearTags($elementId, $type)
    {
        TagRefElement::clearTags($elementId, $type);
    }

    public static function get($tagText)
    {
        $q = Tag::select()
            ->where('site_id = ?', \Bazalt\Site::getId())
            ->andWhere('LOWER(body) LIKE ?', mb_strtolower($tagText))
            ->limit(1);
        return $q->fetch();
    }

    public static function find($tagText, $limit = 10)
    {
        $q = Tag::select()
            ->where('site_id = ?', \Bazalt\Site::getId())
            ->andWhere('LOWER(body) LIKE ?', '%' . mb_strtolower($tagText) . '%')
            ->limit($limit);
        return $q->fetchAll();
    }

    public function toArray()
    {
        $res = parent::toArray();

        return $res;
    }


    public static function filterByTags($collection, $tags, $type, $alias = null)
    {
        if (!$alias) {
            $alias = 'f';
        }

        if (count($tags) > 0) {
            $i = 0;
            foreach ($tags as $itm) {
                $tag = Tag::get($itm);
                if ($tag) {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te_' . $i . '',
                        ' ON te_' . $i . '.element_id = ' . $alias . '.id AND te_' . $i . '.type = "' . $type . '" AND te_' . $i . '.tag_id = ' . (int)$tag->id . ' ');
                } else {
                    $collection->innerJoin('Modules\\Tags\\Model\\TagRefElement te_' . $i . '',
                        ' ON te_' . $i . '.element_id = ' . $alias . '.id AND te_' . $i . '.type = "' . $type . '" AND te_' . $i . '.tag_id = ' . 0 . ' ');
                }

                $i++;
            }
        }

        return $collection;
    }
}
