<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;
use Modules\Tests\Model\ResultRefAnswer;

class Result extends Base\Result
{
    const STATUS_STARTED = 'started';

    const STATUS_FINISHED = 'finished';

    const STATUS_IN_PROGRESS = 'inprogress';

    const STATUS_IN_VERIFICATION = 'verification';

    const STATUS_FAIL = 'fail';

    public static function getCollection()
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result f');

        return new \Bazalt\ORM\Collection($q);
    }

    public static function getLastActive($testId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result f')
            ->where('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('f.user_id = ?', \Bazalt\Auth::getUser()->id)
            ->andWhere('(f.status = ? OR f.status = ?)', [self::STATUS_STARTED, self::STATUS_IN_PROGRESS])
            ->andWhere('f.test_id = ?', $testId)
            ->limit(1)
            ->orderBy('f.id DESC');

        $res = $q->fetch();

//        if ($res && $res->status == 'finished') {
//            $res->status = null;
//        }
        return $res;
    }

    public static function getByUser($testId, $userId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result f')
            ->where('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('f.user_id = ?', $userId)
            ->andWhere('f.test_id = ?', $testId)
            ->orderBy('f.id');
        $res = $q->fetchAll();
        $i = 1;
        foreach ($res as &$itm) {
            $itm->attempt_number = $i++;
        }
        return $res;
    }

    public static function getLastFinished($testId, $taskId = null)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result f')
            ->where('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('f.user_id = ?', \Bazalt\Auth::getUser()->id)
            ->andWhere('(f.status = ? OR f.status = ?)', [self::STATUS_FINISHED, self::STATUS_FAIL])
            ->andWhere('f.test_id = ?', $testId);

        if ($taskId) {
            $q->andWhere('f.task_id = ?', (int)$taskId);
        }

        $q->limit(1);
        $q->orderBy('f.id DESC');
        return $q->fetch();
    }

    public static function getFinishedCount($testId, $task = null)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Result f', 'COUNT(*) as cnt')
            ->where('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('f.user_id = ?', \Bazalt\Auth::getUser()->id)
            ->andWhere('(f.status = ? OR f.status = ? OR f.status = ?)', [self::STATUS_FINISHED, self::STATUS_FAIL, self::STATUS_IN_VERIFICATION])
            ->andWhere('f.test_id = ?', $testId);
        if($task) {
            $q->andWhere('f.task_id = ?', $task->id);
        }

        return (int)$q->fetch('\stdClass')->cnt;
    }

    public function saveAnswer($question, $answer)
    {

        $ref = new ResultRefAnswer();
        switch ($question->type) {
            case 'single' :
            case 'multi' :

                $ref->result_id = $this->id;
                $ref->question_id = $question->id;
                $ref->answer_id = $answer->id;
                $ref->is_right = $answer->is_right;
                $ref->save();
                break;
            case 'free' :
                $ref->result_id = $this->id;
                $ref->question_id = $question->id;
                $ref->text_answer = (!$answer['text']) ? '' : $answer['text'];
                $ref->save();

                break;
        }
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();

        return $ref->id;
    }

    public static function create($testId)
    {
        $o = new Result();
        $o->site_id = \Bazalt\Site::getId();
        $o->user_id = \Bazalt\Auth::getUser()->id;
        $o->status = self::STATUS_STARTED;
        $o->test_id = $testId;
        return $o;
    }

    public static function getStepTest($id)
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', 'COUNT(DISTINCT(r.question_id)) as number')
            ->where('r.result_id = ?', $id);
        $res = $q->fetch();
        return (int)$res->number;
    }

    public static function canGetMark($resultId)
    {
        if ($resultId) {
            $res = ResultRefAnswer::getAnswersByResultId($resultId);

            $freeAnswersNotMark = [];
            foreach ($res as $answer) {
                if ($answer->answer_id == 0 && $answer->mark == null) {
                    $freeAnswersNotMark[] = $answer;
                }
            }

            if (count($freeAnswersNotMark) > 0) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['created_at'] = $this->created_at;
        $res['settings'] = unserialize($this->settings);
        return $res;
    }
}
