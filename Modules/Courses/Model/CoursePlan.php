<?php
namespace Modules\Courses\Model;

use Bazalt\ORM;

use Modules\Tests\Model\Test;
use Modules\Resources\Model\Page;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;

class CoursePlan extends Base\CoursePlan
{
    public static function create($courseId)
    {
        $o = new CoursePlan();
        $o->course_id = $courseId;
        return $o;
    }

    public function getElementObject()
    {
        switch($this->type) {
            case 'resource';
            case 'page';
            case 'file';
            case 'url';
                return Page::getById((int)$this->element_id);
            case 'test';
                return Test::getById((int)$this->element_id);
        }
        throw new \Exception(sprintf('Unknown type "%s"', $this->type));
    }

    public static function getList($courseId, $type = null)
    {
        $q = null;
        if ($type) {
            if ($type == 'resource') {
                $q = self::getResourcesQuery($courseId);
            } else if ($type == 'test') {
                $q = self::getTestsQuery($courseId);
            }

        } else {
            $qt = self::getTestsQuery($courseId);
            $qr = self::getResourcesQuery($courseId);
            $q = ORM::union($qt, $qr);
        }

        $q->orderBy('ordr');
//        echo $q->toSQL();exit;
        return $q->fetchAll('\stdClass');
    }

    public static function getTestsQuery($courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan p',
            'p.id, p.element_id, p.type, p.start_element, e.title, p.order as ordr, \'test\' as sub_type')
            ->innerJoin('Modules\\Tests\\Model\\Test e', ['id', 'p.element_id'])
            ->where('p.type = ?', 'test')
            ->andWhere('p.course_id = ?', $courseId);
        return $q;
    }

    public static function getResourcesQuery($courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan p',
            'p.id, p.element_id, p.type, p.start_element, e.title, p.order, e.type as sub_type')
            ->innerJoin('Modules\\Resources\\Model\\Page e', ['id', 'p.element_id'])
            ->where('p.type = ?', 'resource')
            ->andWhere('p.course_id = ?', $courseId);
        return $q;
    }

    public static function getElementsList($courseId, $title = null)
    {
        $existQ = ORM::select('Modules\\Courses\\Model\\CoursePlan p', 'p.element_id')
            ->where('p.type = ?', 'test')
            ->andWhere('p.course_id = ?', $courseId);
        $q = ORM::select('Modules\\Courses\\Model\\CourseElement ce', 'ce.*,e.title,ce.type as sub_type')
            ->innerJoin('Modules\\Tests\\Model\\Test e', ['id', 'ce.element_id'])
            ->where('ce.type = ?', 'test')
            ->andWhere('ce.course_id = ?', $courseId)
            ->andNotWhereIn('ce.element_id', $existQ);

        if (isset($title) && !empty($title)) {
            $q->andWhere('e.title LIKE ? ', '%' . strtolower($title) . '%');
        }

        $res = $q->fetchAll();
        $ret = [];
        foreach ($res as $itm) {
            $ret[$itm->order] = $itm;
        }

        $existQ = ORM::select('Modules\\Courses\\Model\\CoursePlan p', 'p.element_id')
            ->where('p.type = ?', 'resource')
            ->andWhere('p.course_id = ?', $courseId);
        $q = ORM::select('Modules\\Courses\\Model\\CourseElement ce', 'ce.*,e.title,e.type as sub_type')
            ->innerJoin('Modules\\Resources\\Model\\Page e', ['id', 'ce.element_id'])
            ->where('ce.type = ?', 'resource')
            ->andWhere('ce.course_id = ?', $courseId)
            ->andNotWhereIn('ce.element_id', $existQ);

        if (isset($title) && !empty($title)) {
            $q->andWhere('e.title LIKE ? ', '%' . strtolower($title) . '%');
        }

        $res = $q->fetchAll();
        foreach ($res as $itm) {
            $ret[$itm->order] = $itm;
        }

        ksort($ret);
        return $ret;
    }

    public static function searchResource($courseId, $type, $title = null, $tag = null)
    {
        $elementType = $type;
        switch ($type) {
            case 'test':
                $collection = Test::search($title, $tag);
                break;
            case 'page':
            case 'file':
            case 'url':
            case 'html':
                $collection = Page::search($type, $title, null, $tag);
                $elementType = 'resource';
                break;
            default:
                throw new \Exception(sprintf('Unknown type "%s"', $type));
        }

        $existQ = ORM::select('Modules\\Courses\\Model\\CoursePlan cp', 'cp.element_id')
            ->where('cp.type = ?', $elementType)
            ->andWhere('cp.course_id = ?', $courseId);
        $collection->andNotWhereIn('f.id', $existQ);

        return $collection;
    }

    public static function getElement($elementId, $type, $courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CourseElement ce', 'ce.element_id')
            ->where('ce.element_id = ?', $elementId)
            ->andWhere('ce.type = ?', $type)
            ->andWhere('ce.course_id = ?', $courseId);
        return $q->fetch();
    }

    public static function getPlanItem($elementId, $type, $courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan ce', 'ce.*')
            ->where('ce.element_id = ?', $elementId)
            ->andWhere('ce.type = ?', $type)
            ->andWhere('ce.course_id = ?', $courseId)
            ->limit(1);
//        echo $q->toSql();
        return $q->fetch();
    }

    public static function clearIsFinalTest($courseId)
    {
        $q = ORM::update('Modules\\Courses\\Model\\CoursePlan')
            ->set('is_determ_final_mark', 0)
            ->where('course_id = ?', $courseId);
        $q->exec();
    }

    public static function setStartElement($courseId, $startElementId)
    {
        if ($courseId) {
            $q = ORM::select('Modules\\Courses\\Model\\CoursePlan ce', '*')
                ->where('ce.course_id = ?', $courseId);
            $elements = $q->fetchAll();

            foreach ($elements as $item) {
                $element = CoursePlan::getById($item->id);

                if ($element->start_element == 1) {
                    $element->start_element = null;
                    $element->save();
                }
            }
        }

        if ($startElementId) {
            $elem = CoursePlan::getById($startElementId);
            $elem->start_element = 1;
            $elem->save();
        }

    }

    public static function getFinishElement($courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan ce', 'ce.*')
            ->where('ce.course_id = ?', $courseId)
            ->andWhere('ce.is_determ_final_mark = ?', 1)
            ->limit(1);
//        echo $q->toSql();exit;
        return $q->fetch();
    }

    public static function getStartElement($courseId)
    {
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan ce', 'ce.*')
            ->where('ce.course_id = ?', $courseId)
            ->andWhere('ce.start_element = ?', 1)
            ->limit(1);
        $res = $q->fetch();
        if (!$res) {
            $q = ORM::select('Modules\\Courses\\Model\\CoursePlan ce', 'ce.*')
                ->where('ce.course_id = ?', $courseId)
                ->orderBy('ce.order')
                ->limit(1);
            $res = $q->fetch();
        }
        return $res;
    }

    public function save()
    {
        $isNew = $this->isPKEmpty();

        parent::save();

        if($isNew) {//sync tasks
            $this->title = $this->getElementObject()->title;
            $courseTasks = Task::getByCourseId($this->course_id);
            foreach($courseTasks as $courseTask) {
                $subTask = Task::createFromPlanElement($courseTask, $this);
                $cnt = $courseTask->Elements->count();
                $courseTask->Elements->insert($subTask, $cnt);

                $users = $courseTask->getUsers();
                foreach($users as $user) {
                    $ref = TaskRefUser::getByUserAndTask($courseTask->id, $user->id);
                    if($ref->status != TaskRefUser::STATUS_FINISHED) {
                        TaskRefUser::assignUser($subTask->id, $user->id);
                    }
                }
            }
        }
    }

    public function delete()
    {
        $courseTasks = Task::getByCourseId($this->course_id);
        foreach($courseTasks as $courseTask) {
            $subTask = Task::getByElement($courseTask->id, $this->element_id, $this->type);
            if($subTask) {
                $courseTask->Elements->remove($subTask);
            }
        }

        parent::delete();
        
        //update orders
        $q = ORM::select('Modules\\Courses\\Model\\CoursePlan p', 'p.*')
            ->where('p.course_id = ?', $this->course_id)
            ->orderBy('p.order');
        $items = $q->fetchAll();
        $i = 0;
        foreach($items as $item) {
            $item->order = $i++;
            $item->save();
        }
    }

    public static function resortingTasks($items)
    {
        $courseTasks = Task::getByCourseId($items[0]->course_id);
        foreach($courseTasks as $courseTask) {
            $taskOrders = [];
            $i = 1;
            foreach($items as $planItem) {
                $subTask = Task::getByElement($courseTask->id, $planItem->element_id, $planItem->type);
                if($subTask) {
                    $taskOrders[$subTask->id] = $i;
                    $i++;
                }
            }
            Task::resorting($courseTask, $taskOrders);
        }
    }
}
