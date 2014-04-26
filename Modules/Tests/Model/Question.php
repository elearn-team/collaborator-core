<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;

class Question extends Base\Question
{
    const TYPE_SINGLE_ANSWER = 'single';

    const TYPE_MULTI_ANSWER = 'multi';

    const TYPE_FREE_ANSWER = 'free';

    public static function getCollection($testId)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Question f')
            ->where('f.test_id = ?', $testId)
            ->andWhere('f.is_deleted != ?', 1);

        return new \Bazalt\ORM\Collection($q);
    }

    public static function create()
    {
        $o = new Question();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['allow_add_files'] = (bool)$res['allow_add_files'];
        $res['files'] = [];
        $files = $this->Files->get();
        foreach ($files as $file) {
            $res['files'][] = $file->toArray();
        }
        return $res;
    }

    public function getResult($res)
    {
        switch ($this->type) {
            case self::TYPE_SINGLE_ANSWER:
                return $this->getResultSingle($res);
            case self::TYPE_MULTI_ANSWER:
                return $this->getResultMulti($res);
            case self::TYPE_FREE_ANSWER:
                return $this->getResultFree($res);
        }
        throw new \Exception(sprintf('Unkown type "%s"', $this->type));
    }

    public function getResultSingle($res)
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', 'r.*')
            ->where('r.result_id = ?', $res->id)
            ->andWhere('r.question_id = ?', $this->id)
            ->limit(1);
        $answRes = $q->fetch();
        if ($answRes) {
            return (int)$answRes->is_right * (float)$this->weight;
        } else {
            return 0;
        }
    }

    public function getResultMulti($res)
    {
        $q = ORM::select('Modules\\Tests\\Model\\Answer a', 'a.*,r.is_right as user_is_right')
            ->leftJoin('Modules\\Tests\\Model\\ResultRefAnswer r', ['answer_id', 'a.id'])
            ->where('r.result_id = ?', $res->id)
            ->andWhere('a.question_id = ?', $this->id)
            ->andWhere('r.question_id = ?', $this->id)
            ->andWhere('a.is_deleted != ?', 1);
        $answRes = $q->fetchAll();

        $sums = [];
        $wrong = [];

        $query = ORM::select('Modules\\Tests\\Model\\Answer a', 'a.*')
            ->where('a.question_id = ?', $this->id)
            ->andWhere('a.is_right = ?', 1)
            ->andWhere('a.is_deleted != ?', 1);
        $correctAnswers = $query->fetchAll();

        $query = ORM::select('Modules\\Tests\\Model\\Answer a', 'a.*')
            ->where('a.question_id = ?', $this->id)
            ->andWhere('a.is_deleted != ?', 1);
        $allAnswers = $query->fetchAll();

        foreach ($answRes as $itm) {
            if ($itm->user_is_right == 1 && $itm->is_right == 1) {
                $sums [] = 1;
            }

            if ($itm->user_is_right == 0 && $itm->is_right == 0) {
                $wrong [] = 1;
            }
        }

        if (count($wrong) > 0 && count($sums) > 0) {
            return (array_sum($sums) / count($allAnswers)) * (float)$this->weight;
        } elseif (count($sums) > 0) {
            return (array_sum($sums) / count($correctAnswers)) * (float)$this->weight;
        } else {
            return 0;
        }

    }

    public function getResultFree($res)
    {
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', 'r.*')
            ->where('r.result_id = ?', $res->id)
            ->andWhere('r.question_id = ?', $this->id)
            ->limit(1);
        $answRes = $q->fetch();
        if ($answRes) {
            return $answRes->mark;
        } else {
            return 0;
        }
    }
}
