<?php
namespace Modules\Courses\Model;

use Bazalt\ORM;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;

class CourseTestSetting extends Base\CourseTestSetting
{
    public static function getByCourseId($courseId, $testId)
    {
        $q = CourseTestSetting::select()
            ->where('course_id = ?', $courseId)
            ->andWhere('test_id = ?', $testId)
            ->limit(1);
        return $q->fetch();
    }

    public static function create($courseId, $testId)
    {
        $o = new CourseTestSetting();
        $o->course_id = $courseId;
        $o->test_id = $testId;
        $o->all_questions = true;
        $o->unlim_attempts = true;
        $o->attempts_count = 10;
        $o->threshold = 0;

        return $o;
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['all_questions'] = (bool)$res['all_questions'];
        $res['questions_count'] = (int)$res['questions_count'];
        $res['unlim_attempts'] = (bool)$res['unlim_attempts'];
        $res['attempts_count'] = (int)$res['attempts_count'];
        $res['threshold'] = (int)$res['threshold'];
        $res['training'] = (bool)$res['training'];
        $res['time'] = (int)$res['time'];
        return $res;
    }


    public function save()
    {
        $isNew = $this->isPKEmpty();

        parent::save();

        if(!$isNew) {//sync tasks
            $courseTasks = Task::getByCourseId($this->course_id);
            foreach($courseTasks as $courseTask) {
                $subTask = Task::getByElement($courseTask->id, $this->test_id, Task::TYPE_TEST);
                $subTask->threshold = $this->threshold;
                $subTask->save();
                $users = $courseTask->getUsers();
                foreach($users as $user) {
                    $ref = TaskRefUser::getByUserAndTask($subTask->id, $user->id);
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
}
