<?php
namespace Modules\Tasks\Model;

use Bazalt\ORM;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseTestSetting;
use Modules\Tests\Model\Result;
use Bazalt\Auth\Model\User;

class TaskRefUser extends Base\TaskRefUser
{
    const STATUS_STARTED = 'started';

    const STATUS_FINISHED = 'finished';

    const STATUS_IN_PROGRESS = 'inprogress';

    const STATUS_IN_VERIFICATION = 'verification';

    const STATUS_FAIL = 'fail';

    const RETURN_OK = 1;

    const RETURN_EXISTS = 2;

    const RETURN_OVER_LIMIT = 3;

    protected static function _assignUser($task, $userId, $parent = null)
    {
        if (self::getByUserAndTask($task->id, $userId)) {
            return self::RETURN_EXISTS;
        }
        $ref = new TaskRefUser();
        $ref->task_id = $task->id;
        $ref->user_id = $userId;
        $ref->attempts_limit = 0;

        if ($task->type == Task::TYPE_TEST) {
            if ($parent) {
                $courseTestSett = CourseTestSetting::getByCourseId((int)$parent->element_id, (int)$task->element_id);
                if ($courseTestSett && !$courseTestSett->unlim_attempts) {
                    $ref->attempts_limit = $courseTestSett->attempts_count;
                }
            } else {
                $testSett = TaskTestSetting::getByTaskId($task->id);
                if ($testSett && !$testSett->unlim_attempts) {
                    $ref->attempts_limit = $testSett->attempts_count;
                }
            }
            $ref->attempts_count = Result::getFinishedCount($task->element_id, $task);
            if($ref->attempts_count && $ref->attempts_limit && $ref->attempts_count > $ref->attempts_limit) {
                $ref->attempts_limit = $ref->attempts_count;
            }
        }
        $ref->save();
//        print_r($ref);
        return ($ref->attempts_count > 0 && $ref->attempts_limit > 0 && $ref->attempts_count >= $ref->attempts_limit) ?
            self::RETURN_OVER_LIMIT :
            self::RETURN_OK;
    }

    public static function assignUser($taskId, $userId, $parent = null)
    {
        $err = [];
        $task = Task::getById($taskId);
        if(!$task) {
            return false;
        }
        if($task->type == Task::TYPE_COURSE) {
            $res = self::_assignUser($task, $userId, $task);
//            echo $task->id.','. $userId.','. $res."\n";
            if($res == TaskRefUser::RETURN_OVER_LIMIT) {
                $err []= $task->toArray();
            }
            $subTasks = $task->Elements->get();
            foreach ($subTasks as $subTask) {
                $res = self::_assignUser($subTask, $userId, $task);
//                echo $subTask->id.','. $userId.','. $res."\n";
                if($res == TaskRefUser::RETURN_OVER_LIMIT) {
                    $err []= $subTask->toArray();
                }
            }
//            exit;
        } else {
            $res = self::_assignUser($task, $userId);
            if($res == TaskRefUser::RETURN_OVER_LIMIT) {
                $err []= $task->toArray();
            }
        }
//        print_r($err);
        return $err;
    }

    public static function getByUserAndTask($taskId, $userId)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\TaskRefUser u', 'u.*')
            ->where('task_id = ?', (int)$taskId)
            ->andWhere('user_id = ?', (int)$userId)
            ->limit(1);
        return $q->fetch();
    }

    public static function getTaskIdByCourse($courseId, $userId)
    {
        $q = ORM::select('Modules\\Tasks\\Model\\Task t', 't.id')
            ->innerJoin('Modules\\Tasks\\Model\\TaskRefUser tu', ['task_id', 't.id'])
            ->andWhere('t.element_id = ?', $courseId)
            ->andWhere('t.type = ?', Task::TYPE_COURSE)
            ->andWhere('tu.user_id = ?', $userId)
            ->limit(1);
        $res = $q->fetch();
        return $res ? $res->id : null;
    }

    public static function unassignUser($taskId, $userId)
    {
        $q = ORM::delete('Modules\\Tasks\\Model\\TaskRefUser u')
            ->where('task_id = ?', (int)$taskId)
            ->andWhere('user_id = ?', (int)$userId);
        $q->exec();

        $task = Task::getById($taskId);
        if ($task->type == Task::TYPE_COURSE) {
            $subTasks = $task->Elements->get();
            foreach ($subTasks as $subTask) {
                self::unassignUser($subTask->id, $userId);
            }
        }
    }

    public static function getUsers($taskId)
    {
        $q = ORM::select('Bazalt\\Auth\\Model\\User u', 'u.*,(CASE WHEN (t.user_id IS NULL) THEN 0 ELSE 1 END) as checked')
            ->leftJoin('Modules\\Tasks\\Model\\TaskRefUser t', ' ON t.user_id = u.id AND t.task_id = ' . $taskId . ' ')
            ->orderBy('t.user_id DESC')
            ->where('u.is_deleted = 0');

        return new \Bazalt\ORM\Collection($q);
    }

    public function save()
    {
        Task::clearRefCache();
        return parent::save();
    }
}
