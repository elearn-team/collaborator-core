<?php
namespace Modules\Courses\Model;

use Bazalt\ORM;

use Modules\Tests\Model\Test;
use Modules\Resources\Model\Page;

class CourseElement extends Base\CourseElement
{
    public static function getList($courseId, $title = null, $withoutPlan = null)
    {
        $qt = ORM::select('Modules\\Courses\\Model\\CourseElement ce',
            'ce.id, ce.element_id, ce.type, \'\' as code, \'\' as description, e.title, ce.order as ordr, \'test\' as sub_type')
            ->innerJoin('Modules\\Tests\\Model\\Test e', ['id', 'ce.element_id'])
            ->where('ce.type = ?', 'test')
            ->andWhere('ce.course_id = ?', $courseId);

        if (isset($title) && !empty($title)) {
            $qt->andWhere('e.title LIKE ? ', '%' . strtolower($title) . '%');
        }

        if (isset($withoutPlan) && !empty($withoutPlan)) {
            $existQ = ORM::select('Modules\\Courses\\Model\\CoursePlan p', 'p.element_id')
                ->where('p.course_id = ?', (int)$courseId);
            $qt->andNotWhereIn('ce.element_id', $existQ);
        }


        $qr = ORM::select('Modules\\Courses\\Model\\CourseElement ce',
            'ce.id, ce.element_id, ce.type, e.code, e.description, e.title, ce.order as ordr, e.type as sub_type')
            ->innerJoin('Modules\\Resources\\Model\\Page e', ['id', 'ce.element_id'])
            ->where('ce.type = ?', 'resource')
            ->andWhere('ce.course_id = ?', $courseId);

        if (isset($title) && !empty($title)) {
            $qr->andWhere('e.title LIKE ? ', '%' . strtolower($title) . '%');
        }

        if (isset($withoutPlan) && !empty($withoutPlan)) {
            $existQ = ORM::select('Modules\\Courses\\Model\\CoursePlan p', 'p.element_id')
                ->where('p.course_id = ?', (int)$courseId);
            $qr->andNotWhereIn('ce.element_id', $existQ);
        }

        $q = ORM::union($qt, $qr);
        $q->orderBy('ordr');
//        echo $q->toSQL();exit;
        return $q->fetchAll('\stdClass');
    }

    public static function create($courseId)
    {
        $o = new CourseElement();
        $o->course_id = $courseId;
        return $o;
    }

    public static function searchResource($courseId, $type, $title, $categoryId = null, $tag)
    {
        $elementType = $type;
        switch ($type) {
            case 'All':
                $collection = CourseElement::searchAllResources($title, $courseId, $tag);
                $elementType = 'resource';
                break;
            case 'test':
                $collection = Test::search($title, $tag);
                break;
            case 'page':
            case 'file':
            case 'url':
            case 'html':
                $collection = Page::search($type, $title, isset($categoryId) ? $categoryId : null, $tag);
                $elementType = 'resource';
                break;
            default:
                throw new \Exception(sprintf('Unknown type "%s"', $type));
        }

        $existQ = ORM::select('Modules\\Courses\\Model\\CourseElement ce', 'ce.element_id')
            ->where('ce.type = ?', $elementType)
            ->andWhere('ce.course_id = ?', $courseId);

        $collection->andNotWhereIn('f.id', $existQ);
        return $collection;
    }

    public function getObject()
    {
        switch ($this->type) {
            case 'test':
                return Test::getById((int)$this->element_id);
                break;
            case 'resource':
            case 'page':
            case 'file':
            case 'url':
            case 'html':
                return Page::getById((int)$this->element_id);
                break;
            default:
                throw new \Exception(sprintf('Unknown type "%s"', $this->type));
        }
    }

    public static function resorting($id, $order)
    {
        $q = ORM::update('Modules\\Courses\\Model\\CourseElement')
            ->set('order', $order)
            ->where('id = ?', $id);
        $q->exec();
    }

    public function delete($id = null)
    {
        self::deleteItemOnPlan($this->course_id, $this->element_id, $this->type);

        return parent::delete($id);
    }

    public static function deleteItemOnPlan($courseId, $elementId, $type)
    {
        $q = ORM::delete('Modules\Courses\Model\CoursePlan p')
            ->where('p.course_id = ?', $courseId)
            ->andWhere('p.element_id = ?', $elementId)
            ->andWhere('p.type = ?', $type);

        return $q->exec();
    }

    public static function searchAllResources($title, $courseId, $tag)
    {

        $existQ = ORM::select('Modules\\Courses\\Model\\CourseElement ce', 'ce.element_id')
            ->where('ce.type = "resource"')
            ->andWhere('ce.course_id = ?', $courseId);

        $q1 = ORM::select('Modules\\Resources\\Model\\Page f', 'f.title, f.type , f.id, f.code, f.description')
            ->leftJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id ')
            ->leftJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ')
            ->where('LOWER(f.title) LIKE ?', '%' . mb_strtolower($title) . '%')
            ->andNotWhereIn('f.id', $existQ);

        if(isset($tag)){
            $q1->andWhere('LOWER(t.body) LIKE ?', '%' . mb_strtolower($tag) . '%');
        }

        $q2 = ORM::select('Modules\\Tests\\Model\\Test t', 't.title, "test" as type, t.id, "" as code, t.description')
            ->leftJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = t.id ')
            ->leftJoin('Modules\\Tags\\Model\\Tag ta', ' ON ta.id = te.tag_id ')
            ->andWhere('LOWER(t.title) LIKE ?', '%' . mb_strtolower($title) . '%');

        if(isset($tag)){
            $q2->andWhere('LOWER(ta.body) LIKE ?', '%' . mb_strtolower($tag) . '%');
        }
        $q = ORM::union($q1, $q2);
        return new \Bazalt\ORM\Collection($q);
    }

}
