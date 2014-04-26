<?php
namespace Modules\Tags\Model;

use Bazalt\ORM;

class TagRefElement extends Base\TagRefElement
{
    public static function getElementTags($elementId, $type){
        $q = ORM::select('Modules\\Tags\\Model\\Tag t', 't.*')
            ->leftJoin('Modules\\Tags\\Model\\TagRefElement tr', ' ON tr.tag_id = t.id ')
            ->where('tr.element_id = ?', $elementId)
            ->andWhere('tr.type = ?', $type);
        return $q->fetchAll();
    }

    public static function hasTag($elementId, $type, $tagId)
    {
        $q = ORM::select('Modules\\Tags\\Model\\TagRefElement tr', 'COUNT(*) as cnt')
            ->where('element_id = ?', $elementId)
            ->andWhere('type = ?', $type)
            ->andWhere('tag_id = ?', $tagId);
        return (int)$q->fetch('\stdClass')->cnt > 0;
    }

    public static function addTag($elementId, $type, $tagId)
    {
        $o = new TagRefElement();
        $o->element_id = $elementId;
        $o->type = $type;
        $o->tag_id = $tagId;
        $o->save();
        return $o;
    }

    public static function clearTags($elementId, $type)
    {
        $q = ORM::delete('Modules\\Tags\\Model\\TagRefElement')
            ->where('element_id = ?', $elementId)
            ->andWhere('type = ?', $type);

        return $q->exec();
    }
}
