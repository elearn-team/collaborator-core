<?php
namespace Modules\Tasks\Model;

use Bazalt\ORM;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseTestSetting;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tests\Model\Result;
use Modules\Tests\Model\Test;
use Modules\Resources\Model\Page;


class Task extends Base\Task
{
    const TYPE_TEST = 'test';
    const TYPE_RESOURCE = 'resource';
    const TYPE_COURSE = 'course';

    private static $_refsCache = array();

    public static function getCollection()
    {
        $q = Task::select()
            ->where('is_deleted != ?', 1)
            ->andWhere('depth = ?', 0);
        return new \Bazalt\ORM\Collection($q);
    }

    public static function getByIds($ids)
    {
        $q = Task::select()
            ->where('is_deleted != ?', 1)
            ->andWhereIn('id', $ids);
        return $q->fetchAll();
    }

    public static function getByElement($parentId, $elementId, $type)
    {
        $q = Task::select()
            ->where('is_deleted != ?', 1)
            ->andWhere('parent_id = ?', $parentId)
            ->andWhere('element_id = ?', $elementId)
            ->andWhere('type = ?', $type)
            ->limit(1);
        return $q->fetch();
    }

    public static function getUserCollection($user, $courses)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\Task t', 't.*,u.user_id, u.status, u.mark, u.attempts_count, u.attempts_limit')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser u', ['task_id', 't.id']);
        if ($courses) {
            $q->leftJoin('Modules\\Courses\\Model\\Course c', ' ON c.id = t.element_id ');
        }
        $q->where('u.user_id = ?', $user->id);
        $q->andWhere('t.is_deleted != ?', 1);
        $q->andWhere('t.depth = ?', 0);
        $q->groupBy('t.id');
        if ($courses) {
            $q->andWhere('t.type = ?', 'course');
            $q->andWhere('c.is_published = ?', 1);
        } else {
            $q->andWhere('(t.type = ? OR t.type = ?)', ['test', 'resource']);
        }
        return new \Bazalt\ORM\Collection($q);
    }

    public static function clearRefCache()
    {
        self::$_refsCache = array();
    }

    private function _getRef($user)
    {
        if (isset(self::$_refsCache[$this->id . '_' . $user->id])) {
            return self::$_refsCache[$this->id . '_' . $user->id];
        }
        self::$_refsCache[$this->id . '_' . $user->id] = TaskRefUser::getByUserAndTask($this->id, $user->id);
        return self::$_refsCache[$this->id . '_' . $user->id];
    }

    public function getStatus($user = null)
    {
        if ($user === null) {
            $user = \Bazalt\Auth::getUser();
        }
        $ref = $this->_getRef($user);
        return $ref ? $ref->status : '';
    }

    public function getMark($user = null)
    {
        if ($user === null) {
            $user = \Bazalt\Auth::getUser();
        }
        $ref = $this->_getRef($user);
        return $ref ? $ref->mark : 0;
    }

    public function getAttemptsCount($user = null)
    {
        if ($user === null) {
            $user = \Bazalt\Auth::getUser();
        }
        $ref = $this->_getRef($user);
        return $ref ? (int)$ref->attempts_count : 0;
    }

    public function getAttemptsLimit($user = null)
    {
        if ($user === null) {
            $user = \Bazalt\Auth::getUser();
        }
        $ref = $this->_getRef($user);
        return $ref ? (int)$ref->attempts_limit : 0;
    }

    private function _getPlan($itm, $res)
    {
        $elements = $itm->Elements->get();
        $count = 0;
        $toArray = function ($items) use (&$toArray, &$count) {
            $result = [];
            foreach ($items as $key => $item) {
                $count++;
                $res = $item->toSmallArray();
                $res['plan'] = (is_array($item->Childrens) && count($item->Childrens)) ? $toArray($item->Childrens) : [];
                $result[$key] = $res;
            }
            return $result;
        };
        $res['plan'] = $toArray($elements);
        return $res;
    }

    public function toSmallArray()
    {
        $res = [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->getStatus(),
            'mark' => (float)$this->getMark(),
            'threshold' => (float)$this->threshold,
            'is_success' => (float)(float)$this->getMark() > (float)$this->threshold,
            'plan' => [],
            'can_execute' => $this->canExecute(),
            'attempts_count' => (int)$this->getAttemptsCount(),
            'attempts_limit' => $this->getAttemptsLimit()
        ];
        return $res;
    }

    public function toArray()
    {
        $res = parent::toArray();

        $res['status'] = $this->getStatus();
        $res['mark'] = $this->getMark();
        $res['attempts_count'] = (int)$this->getAttemptsCount();
        $res['attempts_limit'] = (int)$this->getAttemptsLimit();
        $res['plan'] = [];
        $res['plan_percent_complete'] = 0;
        $res['plan_sum'] = 0;
        $res['plan_percent'] = 0;
        $res['plan_sum_max'] = 0;

        if ($this->parent_id) {

            if ($this->parent_id != $this->id) {
                $res['parent'] = self::getById((int)$this->parent_id)->toArray();
            }
            unset($res['Childrens']);
            if ($this->depth == 0) {
                $res = $this->_getPlan($this, $res);
            } else {
                $res = $this->_getPlan($this->Elements->getParent(), $res);
            }
            $count = count($res['plan']);
            $successDoneCount = 0;
            $sum = 0;
            $successSum = 0;
            foreach ($res['plan'] as $taskArr) {
                if ($taskArr['status'] == TaskRefUser::STATUS_FINISHED || $taskArr['status'] == TaskRefUser::STATUS_FAIL) {
                    $successDoneCount++;
                }
                $successSum += (float)$taskArr['mark'];
                $sum += (float)$taskArr['mark'];
            }
            $res['plan_percent_complete'] = $count > 0 ? ceil($successDoneCount * 100 / $count) : 0;
            $res['plan_sum_max'] = $count * 100;
            $res['plan_sum'] = ceil($sum);
            $res['plan_percent'] = $count > 0 ? ceil($successSum / $count) : 0;
        }

        $res['is_success'] = (float)$res['mark'] > (float)$res['threshold'];
        $res['element'] = $this->getElementAsArray();
        $res['start_element'] = $this->getStartElement($res['plan']);
        $res['can_execute'] = $this->canExecute();

        foreach ($res['plan'] as $itm) {
            if ($itm['status'] == 'verification') {
                $res['status'] = TaskRefUser::STATUS_IN_VERIFICATION;
            }
        }

        return $res;
    }

    public function getStartElement($plan)
    {
        switch ($this->type) {
            case Task::TYPE_TEST:
            case Task::TYPE_RESOURCE:
                return null;
            case Task::TYPE_COURSE:
                $course = Course::getById((int)$this->element_id);
                $startElementArr = null;
                if ($course) {
                    if ($course->start_type == Course::START_TYPE_PLAN) {
                        $startElement = CoursePlan::getStartElement($this->element_id);
                        if($startElement) {
                            $startElementTask = Task::getByElement($this->id, $startElement->element_id, $startElement->type);
                            $startElementArr = $startElementTask ? $startElementTask->toSmallArray() : null;
                        }
                    }
                    foreach ($plan as $key => $item) {
                        if ($item['status'] == TaskRefUser::STATUS_IN_PROGRESS) {
                            $startElementArr = $item;
                        }
                    }
                }
                return $startElementArr;
                break;
        }
        throw new \Exception(sprintf('Unknown type "%s"', $this->type));
    }

    public function canExecute()
    {
        $canExecute = false;
        switch ($this->type) {
            case Task::TYPE_TEST:
                $user = \Bazalt\Auth::getUser();
                $ref = $this->_getRef($user);
                if ($ref) {
                    $attemptsCount = $this->getAttemptsCount();
                    $attemptsLimit = $this->getAttemptsLimit();
                    $canExecute = true;
                    if ($attemptsLimit > 0) {
                        $canExecute = $attemptsCount < $attemptsLimit;
                    }
                } else {
                    $canExecute = false;
                }
                break;
            case Task::TYPE_RESOURCE:
            case Task::TYPE_COURSE:
                $canExecute = true;
                break;
            default:
                throw new \Exception(sprintf('Unknown type "%s"', $this->type));
        }
        return $canExecute;
    }

    public function getElementAsArray()
    {
        $el = null;
        switch ($this->type) {
            case Task::TYPE_TEST:
                $el = Test::getById((int)$this->element_id);
                break;
            case Task::TYPE_RESOURCE:
                $el = Page::getById((int)$this->element_id);
                break;
            case Task::TYPE_COURSE:
                $el = Course::getById((int)$this->element_id);
                break;
            default:
                throw new \Exception(sprintf('Unknown type "%s"', $this->type));
        }
        return $el ? $el->toArray() : null;
    }

    public static function getByCourseId($courseId)
    {
        $q = Task::select()
            ->where('f.is_deleted != ?', 1)
            ->andWhere('f.type = ?', 'course')
            ->andWhere('f.element_id = ?', $courseId);
        return $q->fetchAll();
    }

    public static function create()
    {
        $o = new Task();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public static function createFromPlanElement($parent, $planItem)
    {
        $o = Task::create();
        $o->parent_id = $parent->id;
        $o->element_id = $planItem->element_id;
        $o->title = $planItem->title;
        $o->type = $planItem->type;

        if ($o->type == Task::TYPE_TEST) {
            $testSett = CourseTestSetting::getByCourseId($parent->element_id, $planItem->element_id);
            if ($testSett) {
                $o->threshold = $testSett->threshold;
            }
        }
        return $o;
    }

    public function saveForCourse()
    {
        $this->lft = 1;
        $this->rgt = 2;
        $this->depth = 0;

        $this->save();

        $this->parent_id = $this->id;
        $this->save();

        //get plan
        $planItems = CoursePlan::getList($this->element_id);
        krsort($planItems);
        foreach ($planItems as $planItem) {
            $subTask = Task::createFromPlanElement($this, $planItem);
            $this->Elements->insert($subTask);
        }
    }

    public function checkCourseState($userId, $reloadMark = null)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser tu', 'tu.*')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'tu.task_id'])
            ->where('t.is_deleted != ?', 1)
            ->andWhere('t.type != ?', 'course')
            ->andWhere('t.parent_id = ?', $this->parent_id)
            ->andWhere('tu.user_id = ?', $userId);
        $subTasks = $q->fetchAll();
        $finished = true;

        foreach ($subTasks as $subTask) {
            $finished &= $subTask->status == TaskRefUser::STATUS_FINISHED || $subTask->status == TaskRefUser::STATUS_FAIL;
        }

        if ($finished) {
            $task = Task::getById($this->parent_id);
            $courseTask = Task::getById((int)$this->parent_id);
            $userTask = TaskRefUser::getByUserAndTask($courseTask->id, $userId);
            if ($userTask->status != TaskRefUser::STATUS_FINISHED || $reloadMark) {
                $mark = self::calcCoursePercent($courseTask, $userId);
                $userTask->status = ($mark < $task->threshold) ? TaskRefUser::STATUS_FAIL : TaskRefUser::STATUS_FINISHED;
                $userTask->attempts_count = 1;
                $userTask->mark = $mark;
                $userTask->save();
            }
        }
    }

    public static function getSubTaskMark($parentId, $type, $elementId, $userId)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser tu', 'tu.mark')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'tu.task_id'])
            ->where('t.is_deleted != ?', 1)
            ->andWhere('t.type != ?', 'course')
            ->andWhere('t.parent_id = ?', $parentId)
            ->andWhere('t.type = ?', $type)
            ->andWhere('t.element_id = ?', $elementId)
            ->andWhere('tu.user_id = ?', $userId)
            ->limit(1);
//        echo $q->toSql()."\n";
        $res = $q->fetch('\stdClass');
        return $res ? (float)$res->mark : 0;
    }

    public static function calcCoursePercent($courseTask, $userId)
    {
        $course = Course::getById((int)$courseTask->element_id);
        if ($course->finish_type == Course::FINISH_TYPE_BY_TEST) {
            $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser tu', 'tu.*')
                ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'tu.task_id'])
                ->innerJoin('Modules\\Courses\\Model\\CoursePlan cp', ' ON cp.element_id = t.element_id AND cp.type = t.type AND cp.is_determ_final_mark = 1 ')
                ->where('t.is_deleted != ?', 1)
                ->andWhere('t.parent_id = ?', $courseTask->id)
                ->andWhere('tu.user_id = ?', $userId)
                ->limit(1);
            $task = $q->fetch();
            return $task ? $task->mark : 0;
        } else {
            $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser tu', 'tu.*,t.threshold')
                ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'tu.task_id'])
                ->where('t.is_deleted != ?', 1)
                ->andWhere('t.type != ?', 'course')
                ->andWhere('t.parent_id = ?', $courseTask->id)
                ->andWhere('tu.user_id = ?', $userId);
            $subTasks = $q->fetchAll();
            $successCount = 0;
            $count = 0;
            foreach ($subTasks as $subTask) {
                if ($subTask->threshold) {
                    if ((float)$subTask->mark >= (float)$subTask->threshold) {
                        $successCount++;
                    }
                } else if ($subTask->status) {
                    $successCount++;
                }
                $count++;
            }
            return $count > 0 ? ceil($successCount * 100 / $count) : 0;
        }
    }

    public function getNextSubTask()
    {
        $parent = Task::getById((int)$this->parent_id);
        $elements = $parent->Elements->get();
        $i = 0;
        $l = count($elements);
        $nextTask = null;
        for (; $i < $l; $i++) { //todo make it by SQL
            if ($elements[$i]->id == $this->id && $i + 1 < $l) {
                if ($elements[$i + 1] && $elements[$i + 1]->type == 'test') {
                    $canExecute = $elements[$i + 1]->canExecute();
                    if ($canExecute === false) {
                        if (isset($elements[$i + 2])) {
                            $nextTask = $elements[$i + 2];
                        } else {
                            $nextTask = null;
                        }
                    } else {
                        $nextTask = $elements[$i + 1];
                    }
                } else {
                    $nextTask = $elements[$i + 1];
                }
                break;
            }
        }
        return $nextTask;
    }

    public static function resorting($parent, $orders)
    {
        ORM::begin();
        foreach ($orders as $id => $order) {
            $q = ORM::update('Modules\\Tasks\\Model\\Task')
                ->set('lft', ($order * 2))
                ->set('rgt', ($order * 2) + 1)
                ->where('id = ?', $id);
            $q->exec();
        }
        if (!$parent->Elements->analyze()) {
            ORM::rollBack();
            return false;
        }
        ORM::commit();
        return true;
    }

    public function getUsers()
    {
        $q = ORM::select('Bazalt\\Auth\\Model\\User u', 'u.*')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser t', ' ON t.user_id = u.id AND t.task_id = ' . $this->id . ' ')
            ->orderBy('t.user_id DESC')
            ->where('u.is_deleted = 0');

        return $q->fetchAll();
    }


    public function getTestReportCollection($taskId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result r',
            'u.id as user_id,' .
            'u.firstname, u.secondname, u.patronymic, u.login,' .
            'r.created_at as start_date,' .
            'r.updated_at as finish_date,' .
            'r.status,' .
            'r.mark,' .
            'r.id as result_id,' .
            'r.task_id,' .
            't.parent_id')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'r.task_id'])
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'r.user_id'])
            ->where('r.task_id = ?', $taskId)
            ->groupBy('r.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public function getTestAttemptsCollection($taskId)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser tu',
            'u.id as user_id,' .
            'u.firstname, u.secondname, u.patronymic, u.login,' .
            'tu.created_at,' .
            'tu.status,' .
            'tu.mark,' .
            'tu.task_id as task_id,' .
            't.parent_id as task_parent_id,' .
            'tu.attempts_limit,' .
            'tu.attempts_count')
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'tu.user_id'])
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'tu.task_id'])
            ->where('tu.task_id = ?', $taskId);
        return new \Bazalt\ORM\Collection($q);
    }

    public static function getAllTestsReportCollection()
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result r',
            'u.id as user_id,' .
            'u.firstname, u.secondname, u.patronymic, u.login,' .
            'r.created_at as start_date,' .
            'r.updated_at as finish_date,' .
            'r.status,' .
            'r.mark,' .
            'r.id as result_id,' .
            'r.task_id,' .
            't.parent_id')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'r.task_id'])
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'r.user_id'])
            ->where('t.parent_id is null')
            ->groupBy('r.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public static function getTests()
    {
        $q = Task::select()
            ->where('type = ?', self::TYPE_TEST)
            ->andWhere('is_deleted = ?', 0)
            ->andWhere('parent_id is null')
            ->orderBy('title ASC');
        return $q->fetchAll();
    }

    public function getResourceReportCollection($taskId)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser r',
            'r.*, ' .
            'u.id as user_id,' .
            'u.firstname,' .
            'u.secondname,' .
            'u.patronymic,' .
            'u.login')
            ->innerJoin('Modules\\Tasks\\Model\\Task t', ['id', 'r.task_id'])
            ->innerJoin('Bazalt\Auth\Model\User u', ['id', 'r.user_id'])
            ->where('t.type = ?', self::TYPE_RESOURCE)
            ->andWhere('r.task_id = ?', $taskId)
            ->groupBy('r.id');
        return new \Bazalt\ORM\Collection($q);
    }
}
