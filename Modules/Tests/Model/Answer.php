<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;

class Answer extends Base\Answer
{
    public static function getCollection($questionId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Answer f')
            ->where('f.question_id = ?', $questionId)
            ->andWhere('f.is_deleted != ?', 1);

        return new \Bazalt\ORM\Collection($q);
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['is_right'] = (bool)$res['is_right'];
        return $res;
    }

    public static function create()
    {
        $o = new Answer();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public static function getAnswersListForEvaluation()
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer ra', 'ra.text_answer,
                          q.body as question, q.weight, t.`type`, t.title, t.threshold,
                          te.title as test_title, u.firstname , u.secondname, u.patronymic,
                          t.id as task_id, ra.id, t.title as task_title, t.parent_id, u.email,
                          q.id as question_id, ra.updated_at, ra.mark')
            ->leftJoin('Modules\\Tests\\Model\\Question q', ['id', 'ra.question_id'])
            ->leftJoin('Modules\\Tests\\Model\\Result r', ['id', 'ra.result_id'])
            ->leftJoin('Modules\\Tasks\\Model\\Task t', ['id', 'r.task_id'])
            ->leftJoin('Modules\\Tests\\Model\\Test te', ['id', 'r.test_id'])
            ->leftJoin('Bazalt\\Auth\\Model\\User u', ['id', 'r.user_id'])
            ->where('ra.answer_id = ?', 0)
            ->andWhere('r.status = ? or r.status = ? or r.status = ?', [Result::STATUS_FINISHED, Result::STATUS_IN_VERIFICATION, Result::STATUS_FAIL]);
        return new \Bazalt\ORM\Collection($q);
    }

    public static function getAnswersListForEvaluationDashboard($limit = 10)
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer ra', 'COUNT(ra.id) as all_answers_count,r.task_id')
            ->leftJoin('Modules\\Tests\\Model\\Question q', ['id', 'ra.question_id'])
            ->leftJoin('Modules\\Tests\\Model\\Result r', ['id', 'ra.result_id'])
            ->where('ra.answer_id = ?', 0)
            ->andWhere('r.status = ?', Result::STATUS_IN_VERIFICATION)
            ->groupBy('r.task_id')
            ->limit($limit);
        return $q->fetchAll('\stdClass');
    }

    public static function getAnswersNotRated($taskId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer ra', 'COUNT(ra.id) as cnt')
            ->leftJoin('Modules\\Tests\\Model\\Result r', ['id', 'ra.result_id'])
            ->where('ra.answer_id = ?', 0)
            ->andWhere('ra.mark IS NULL')
            ->andWhere('r.task_id = ?', $taskId);
        return (int)$q->fetch('\stdClass')->cnt;
    }
}
