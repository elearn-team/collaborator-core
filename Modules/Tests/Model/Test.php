<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;
use Modules\Tests\Model\AnswerResultFile;
use Modules\Tests\Model\Question;
use Bazalt\Auth\Model\User;

class Test extends Base\Test
{
    public static function getCollection()
    {
        $q = ORM::select('Modules\\Tests\\Model\\Test f', 'f.*, COUNT(q.id) as questions_count')
            ->leftJoin('Modules\\Tests\\Model\\Question q', ' ON q.test_id = f.id AND q.is_deleted != 1 ')
            ->where('f.is_deleted != ?', 1)
            ->groupBy('f.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public static function search($title, $tag)
    {
//        $q = Test::select()
//            ->where('f.is_deleted != ?', 1)
//            ->andWhere('f.site_id = ?', \Bazalt\Site::getId())
//            ->andWhere('LOWER(f.title) LIKE ?', '%' . mb_strtolower($title) . '%');

        $q = ORM::select('Modules\\Tests\\Model\\Test f', 'f.*')
            ->leftJoin('Modules\\Tags\\Model\\TagRefElement te', ' ON te.element_id = f.id ')
            ->leftJoin('Modules\\Tags\\Model\\Tag t', ' ON t.id = te.tag_id ')
            ->where('f.is_deleted != ?', 1)
            ->andWhere('f.site_id = ?', \Bazalt\Site::getId())
            ->andWhere('(LOWER(f.title) LIKE ?)', array(
                '%' . mb_strtolower($title) . '%'));

        if (isset($tag) && $tag != '') {
            $q->andWhere('(LOWER(t.body) LIKE ?)', array(
                '%' . mb_strtolower($tag) . '%'));
        }
        $q->groupBy('f.id');
        return new \Bazalt\ORM\Collection($q);
    }

    public function getRandomQuestion($result, $limit = null)
    {
        $eq = ResultRefAnswer::select('DISTINCT(f.question_id)')
            ->where('f.result_id = ?', $result->id);
        if ($limit) {
            $cq = ResultRefAnswer::select('COUNT(DISTINCT(f.question_id)) as cnt')
                ->where('f.result_id = ?', $result->id);
            $cnt = (int)$cq->fetch('\stdClass')->cnt;
            if ($cnt >= $limit) {
                return null;
            }
        }
        $q = ORM::select('Modules\\Tests\\Model\\Question f')
            ->where('f.test_id = ?', $this->id)
            ->andWhere('f.is_deleted != ?', 1)
            ->andNotWhereIn('f.id', $eq)
            ->limit(1)
            ->orderBy('RAND()');
        return $q->fetch('Modules\\Tests\\Model\\Question');
    }

    public function getAllQuestionsCount()
    {
        $q = ORM::select('Modules\\Tests\\Model\\Question f', 'COUNT(*) as cnt')
            ->where('f.test_id = ?', $this->id)
            ->andWhere('f.is_deleted != ?', 1);
//        echo $q->toSql();exit;
        return (int)$q->fetch('\stdClass')->cnt;
    }

    public function getQuestionsCount()
    {
        $q = ORM::select('Modules\\Tests\\Model\\Question f', 'COUNT(*) as cnt')
            ->where('f.test_id = ?', $this->id)
            ->andWhere('f.is_deleted != ?', 1);
        return (int)$q->fetch('\stdClass')->cnt;
    }

    public function getQuestions($resId = null)
    {
        if (isset($resId)) {
            $q = ORM::select('Modules\\Tests\\Model\\Question f', 'f.*')
                ->leftJoin('Modules\\Tests\\Model\\ResultRefAnswer r', ' ON r.question_id = f.id ')
                ->where('r.result_id = ?', (int)$resId)
                ->andWhere('f.is_deleted != ?', 1);
        } else {
            $q = ORM::select('Modules\\Tests\\Model\\Question f', 'f.*')
                ->where('f.test_id = ?', $this->id)
                ->andWhere('f.is_deleted != ?', 1);
        }
        return $q->fetchAll();
    }


    /**
     * return result in percent 0-100%
     */
    public function getResult($res = null, $questionCount = null)
    {

        if (!$res) {
            $res = Result::getLastFinished($this->id);
        }
        if (!$res) {
            return 0;
        }

            if (isset($questionCount)) {
                $questions = $this->getQuestions($res->id);
            } else {
                $questions = $this->getQuestions();
            }

            $sum = 0;
            $sumWeight = 0;
            if (count($questions) > 0) {
                foreach ($questions as $question) {
                    $sum += $question->getResult($res);
                    $sumWeight += (float)$question->weight;
                }
                return number_format($sum / $sumWeight * 100, 2, '.', '');
            } else {
                return null;
            }

    }

    public static function create()
    {
        $o = new Test();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public function toArray()
    {
        $res = parent::toArray();
        $res['questions_count'] = $this->questions_count;
        $res['tags'] = [];
        $tags = TagRefElement::getElementTags($this->id, Task::TYPE_TEST);
        foreach ($tags as $tag) {
            $res['tags'][] = $tag->body;
        }
        return $res;
    }

    public function getReport($resultId)
    {
        $res = [];

        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', ' r.*, q.* ')
            ->leftJoin('Modules\\Tests\\Model\\Question q', ['id', 'r.question_id'])
            ->where('r.result_id = ?', $resultId)
            ->groupBy('r.question_id');
        $questions = $q->fetchAll();

        $result = Result::getById((int)$resultId);
        if(!$result) {
            return $res;
        }

        $user = null;
        $user = User::getById((int)$result->user_id);
        $userTask = TaskRefUser::getByUserAndTask((int)$result->task_id, (int)$result->user_id);

        if ($result->settings) {
            $res['settings'] = unserialize($result->settings);
        }

        if ($user && $result->settings) {
            $res['task_params'] = [
                'user_full_name' => $user->firstname . ' ' . $user->secondname . ' ' . $user->patronymic,
                'task_start_date' => $result->created_at,
                'task_finish_date' => $result->updated_at,
                'task_status' => $result->status,
                'task_mark' => $result->mark,
                'task_attempts_limit' => $userTask->attempts_limit,
                'user_id' => $result->user_id
            ];

            $allResults = Result::getByUser((int)$result->test_id, (int)$result->user_id);
            foreach($allResults as $rslt) {
                if($rslt->id == $result->id) {
                    $res['task_params']['task_attempt_number'] = $rslt->attempt_number;
                    break;
                }
            }

            $config = \Bazalt\Config::container();
            try {
                $res['task_params']['user_photo_thumb'] = $config['thumb.prefix'] . thumb(SITE_DIR . '/..' . $user->setting('photo'), '128x128', ['crop' => true, 'fit' => true]);
            } catch (\Exception $ex) {
                $res['task_params']['user_photo_thumb'] = $config['uploads.prefix'] . $user->setting('photo');
            }
        }

        if (count($questions) > 0) {
            foreach ($questions as $question) {
                $answers = ORM::select('Modules\\Tests\\Model\\Answer a', 'a.*, r.is_right as checked')
                    ->leftJoin('Modules\\Tests\\Model\\ResultRefAnswer r', ' ON r.answer_id = a.id AND r.result_id = ' . $resultId . ' ')
                    ->where('a.question_id = ?', $question->question_id);

                $answers = $answers->fetchAll();

                $answ = [];

                if (count($answers) > 0) {
                    foreach ($answers as $itm) {

                        $answ[] = [
                            'checked' => $itm->checked,
                            'body' => $itm->body,
                            'answer_true' => $itm->is_right,
                        ];
                    }
                }else{ //free question
                    $resultAnswer = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', 'r.*')
                        ->where('r.result_id = ?', $resultId)
                        ->andWhere('r.question_id = ?', $question->question_id);
                    $resultAnswer = $resultAnswer->fetch();

                    $resultAnswerFiles = AnswerResultFile::getByAnswerResultId($resultAnswer->id);

                    $files = [];

                    if(count($resultAnswerFiles) > 0){
                        foreach($resultAnswerFiles as $file){
                            $files[] = ['name' => $file->name, 'url' => $file->file, 'extension' => $file->extension];
                        }
                    }

                    $answ[] = [
                        'id' => $resultAnswer->id,
                        'text_answer' => $resultAnswer->text_answer,
                        'mark' => $resultAnswer->mark,
                        'files' => $files
                    ];

                }

                $mark = $this->getMark($answers, $question);

                $res['questions'][] = [
                    'body' => $question->body,
                    'type' => $question->type,
                    'weight' => $question->weight,
                    'answers' => $answ,
                    'mark' => round($mark, 1),
                    'result_id' => $resultId,
                    'task_id' => (int)$result->task_id
                ];

            }
        }
        return $res;
    }

    private function getMark($answers, $question)
    {
        if ($question->type === 'single') {
            foreach ($answers as $itm) {
                if ((int)$itm->is_right === 1) {
                    return (int)$itm->is_right * (float)$question->weight;
                }
            }
            return 0;
        } else {
            $wrong = [];
            $correctAnswers = [];
            $sums = [];

            foreach ($answers as $itm) {
                if ($itm->checked == '0') {
                    $wrong[] = $itm->id;
                }
                if ((int)$itm->is_right == 1) {
                    $correctAnswers[] = $itm->id;
                }

                if ((int)$itm->checked == 1 && (int)$itm->is_right == 1) {
                    $sums[] = 1;
                }
            }

            if (count($wrong) > 0 && count($sums) > 0) {
                return round((array_sum($sums) / count($answers)) * (float)$question->weight, 3);
            } elseif (count($sums) > 0) {
                return round((array_sum($sums) / count($correctAnswers)) * (float)$question->weight, 3);
            } else {
                return 0;
            }
        }
    }
}
