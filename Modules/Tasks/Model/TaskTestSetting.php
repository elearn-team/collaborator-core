<?php
namespace Modules\Tasks\Model;

use Bazalt\ORM;

class TaskTestSetting extends Base\TaskTestSetting
{
    public static function getByTaskId($taskId)
    {
        $q = TaskTestSetting::select()
            ->where('task_id = ?', $taskId)
            ->limit(1);
        return $q->fetch();
    }

    public static function create($taskId)
    {
        $o = new TaskTestSetting();
        $o->task_id = $taskId;
        $o->all_questions = true;
        $o->unlim_attempts = true;
        $o->attempts_count = 10;

        return $o;
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['all_questions'] = (bool)$res['all_questions'];
        $res['questions_count'] = (int)$res['questions_count'];
        $res['unlim_attempts'] = (bool)$res['unlim_attempts'];
        $res['attempts_count'] = (int)$res['attempts_count'];
        $res['training'] = (bool)$res['training'];
        $res['time'] = (int)$res['time'];
        $task = Task::getById($res['task_id']);
        if ($task) {
            $res['threshold'] = $task->threshold;
        }


        return $res;

    }

    public function save()
    {
        $isNew = $this->isPKEmpty();

        parent::save();

        if(!$isNew) {//sync tasks
            $task = Task::getById((int)$this->task_id);
            $task->threshold = $this->threshold;
            $task->save();
            $users = $task->getUsers();
            foreach($users as $user) {
                $ref = TaskRefUser::getByUserAndTask($task->id, $user->id);
                if ($this->unlim_attempts) {
                    $ref->attempts_limit = 0;
                } else if($ref->attempts_count < $this->attempts_count) {
                    $ref->attempts_limit = $this->attempts_count;
                }
//                    print_r($ref);
                $ref->save();
            }
        }
    }
}
