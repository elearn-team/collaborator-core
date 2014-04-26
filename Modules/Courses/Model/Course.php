<?php
namespace Modules\Courses\Model;

use Bazalt\ORM;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;

class Course extends Base\Course
{
    const FINISH_TYPE_SUMMARY = 'summary';

    const FINISH_TYPE_BY_TEST = 'by_test';

    const START_TYPE_START_PAGE = 'start_page';

    const START_TYPE_PLAN = 'plan';

    const START_TYPE_ELEMENTS = 'elements';

    public static function getCollection($category = null)
    {
        $q = ORM::select('Modules\\Courses\\Model\\Course c', 'c.*')
            ->where('c.is_deleted != ?', 1);

        if ($category) {
            $childsQuery = ORM::select('Modules\Courses\Model\Category c', 'id')
                ->where('c.lft BETWEEN ? AND ?', array($category->lft, $category->rgt))
                ->andWhere('c.site_id = ?', $category->site_id);

            $q->andWhereIn('c.category_id', $childsQuery);
        }

        $q->groupBy('c.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public function getUsersCollection()
    {
        $q = ORM::select('Modules\\Courses\\Model\\Course c', 'u.*')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ' ON t.element_id = c.id AND t.type = \'course\' ')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser tu', ['task_id', 't.id'])
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'tu.user_id'])
            ->groupBy('u.id');
        return new \Bazalt\ORM\Collection($q);
    }


    public function getReportCollection($taskId = null)
    {
        $q = ORM::select('Modules\\Courses\\Model\\Course c',
            'u.id as user_id,' .
            'u.firstname, u.secondname, u.patronymic, u.login,' .
            'tu.created_at as start_date,' .
            'tu.updated_at as finish_date,' .
            'tu.status,' .
            'tu.mark,' .
            'tu.id as user_task_id,' .
            't.id as task_id,' .
            'c.id as course_id')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ' ON t.element_id = c.id AND t.type = \'course\' ')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser tu', ['task_id', 't.id'])
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'tu.user_id'])
            ->where('c.id = ?', $this->id);

        if ($taskId) {
            $assignUsers = ORM::select('Modules\\Tasks\\Model\\TaskRefUser t', 't.user_id')
                ->where('t.task_id = ?', $taskId);
            $q->andWhere('u.id IN (' . $assignUsers . ')');
        }

        $q->groupBy('tu.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public static function create()
    {
        $o = new Course();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public function toArray()
    {
        $res = parent::toArray();

        $files = $this->Files->get();

        foreach ($files as $file) {
            try {
                $res['files'][] = $file->toArray();
            } catch (\Exception $e) {

            }
        }

        $res['is_published'] = (bool)$res['is_published'];
        $res['registration_for_course'] = (bool)$res['registration_for_course'];
        if ($res['course_length']) {
            if (fmod($res['course_length'], 7)) {
                $res['days'] = $res['course_length'];
            } else {
                $res['week'] = $res['course_length'] / 7;
            }
        }
        if (!$res['icon']) {
            $res['icon'] = $res['icon_thumb'] = '/themes/default/assets/img/defaultcourse.png';
        } else {
            $config = \Bazalt\Config::container();
            try {
                $res['icon_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $this->icon, '250x0');
            } catch (\Exception $ex) {
                $res['icon_thumb'] = '/themes/default/assets/img/defaultcourse.png';
            }
        }
        if (isset($this->task_id)) {
            $res['task_id'] = $this->task_id;
        };

        $res['tags'] = [];
        $tags = TagRefElement::getElementTags($this->id, Task::TYPE_COURSE);
        foreach ($tags as $tag) {
            $res['tags'][] = $tag->body;
        }

        $finishElement = CoursePlan::getFinishElement($this->id);
//        print_r($finishElement->element_id);exit;
        $res['final_test_id'] = $finishElement ? $finishElement->element_id : 0;

        return $res;
    }

    public function export()
    {
        $res = parent::toArray();
        print_r($res);
        exit;

        /*$files = $this->Files->get();

        foreach ($files as $file) {
            try {
                $res['files'][] = $file->toArray();
            } catch (\Exception $e) {

            }
        }


        if (!$res['icon']) {
            $res['icon'] = $res['icon_thumb'] = '/themes/default/assets/img/defaultcourse.png';
        } else {
            $config = \Bazalt\Config::container();
            try {
                $res['icon_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $this->icon, '250x0', ['crop' => true]);
            } catch (\Exception $ex) {
                $res['icon_thumb'] = '/themes/default/assets/img/defaultcourse.png';
            }
        }
        if (isset($this->task_id)) {
            $res['task_id'] = $this->task_id;
        };

        $tags = TagRefElement::getElementTags($this->id, Task::TYPE_COURSE);
        foreach ($tags as $tag) {
            $res['tags'][] = $tag->body;
        }*/
        return $res;
    }

    public function getTestTasks()
    {
        $q = ORM::select('Modules\\Tasks\\Model\\Task t', 't.*')
            ->innerJoin('Modules\\Tasks\\Model\\Task pt', ['id', 't.parent_id'])
            ->where('pt.element_id = ?', $this->id)
            ->andWhere('t.type = ?', Task::TYPE_TEST)
            ->orderBy('t.title ASC');
        return $q->fetchAll();
    }

    public function getAssignUsers()
    {
        $q = ORM::select('Bazalt\\Auth\\Model\\User u', 'u.*')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser tu', ' ON tu.user_id = u.id ')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ' ON t.id = tu.task_id ')
            ->where('t.element_id = ?', (int)$this->id)
            ->andWhere('t.type = ?', 'course')
            ->andWhere('t.is_deleted != ?', 1)
            ->andWhere('u.is_deleted != ?', 1)
            ->groupBy('u.id');
        return new \Bazalt\ORM\Collection($q);
    }
}
