<?php

namespace Modules\Tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;

/**
 * QuestionsResource
 *
 * @uri /tests/:test_id/questions
 */
class QuestionsResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests/:test_id/questions Отримати список питаннь
     * @apiName getList
     * @apiGroup Tests-Question
     * @apiSuccess (200) {Number} id Унікальний id питання
     * @apiSuccess (200) {Number} tests_id Унікальний id тесту
     * @apiSuccess (200) {String} type Тип питання
     * @apiSuccess (200) {Number} weight Вага питання
     * @apiSuccess (200) {String} body Текст питання
     * @apiSuccess (200) {Number} allow_add_files Дозвіл на додавння файлів до відповіді
     * @apiSuccess (200) {Date} created_at Дата створення
     * @apiSuccess (200){Date} updated_at Дата останнього редагування
     */
    public function getList($testId)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Question::getCollection($testId);

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('type')
            ->sortableBy('body')

            ->filterBy('id', function ($collection, $columnName, $value) {
                $collection->andWhere('id = ?', (int)$value);
            })
            ->filterBy('type', function ($collection, $columnName, $value) {
                $collection->andWhere('type = ?', $value);
            })
            ->filterBy('body', function ($collection, $columnName, $value) {
                if ($value) {
                    $value = '%' . strtolower($value) . '%';
                    $collection->andWhere('LOWER(body) LIKE ?', $value);
                }
            })
            ->filterBy('created_at', function ($collection, $columnName, $value) {
                if ($value) {
                    if (is_array($value)) {
                        $collection->andWhere('DATE(f.created_at) BETWEEN ? AND ?', array($value[0], $value[1]));
                    } else {
                        $collection->andWhere('DATE(f.created_at) = ?', $value);
                    }
                }
            });

        return new Response(Response::OK, $table->fetch($_GET));
    }

    /**
     * @method POST
     * @json
     *
     * @api {post} /tests/:test_id/questions Створення питання
     * @apiName createItem
     * @apiGroup Tests-Question
     * @apiSuccessStructure QuestionToArrayStructure
     */
    public function createItem()
    {
        $res = new QuestionResource($this->app, $this->request);
        return $res->saveItem();
    }


    /**
     * @action upload
     * @method POST
     * @accepts multipart/form-data
     * @json
     *
     * @api {post} /tests/:test_id/questions Завантаження файлів
     * @apiName uploadFiles
     * @apiGroup Tests-Question
     * @apiSuccess (200) {String} file Шлях до файлу
     * @apiSuccess (200) {String} name Ім’я файлу
     */
    public function uploadFiles($testId)
    {
        $uploader = new \Bazalt\Rest\Uploader(['jpg', 'png', 'jpeg', 'bmp', 'gif'], 1000000);
        $result = $uploader->handleUpload(UPLOAD_DIR, ['tests', (int)$testId]);
        $result['file'] = '/uploads' . $result['file'];
        $result['url'] = $result['file'];

        return new Response(Response::OK, $result);
    }

    /**
     * @action importQuestions
     * @method POST
     * @accepts multipart/form-data
     * @json
     *
     * @api {post} /tests/:test_id/questions Імпорт питань і відопвідей в тест з файлу
     * @apiName importQuestions
     * @apiGroup Tests-Question
     * @apiSuccess (200) {String} file Шлях до файлу
     * @apiSuccess (200) {String} name Ім’я файлу
     */
    public function importQuestions($testId)
    {
        $uploader = new \Bazalt\Rest\Uploader(['txt'], 1000000);
        $result = $uploader->handleUpload(UPLOAD_DIR, ['tests/questions', (int)$testId]);

        $questions = [];

        if ($str = file_get_contents(UPLOAD_DIR . $result['file'])) {
            $bom = pack("CCC", 0xef, 0xbb, 0xbf);
            if (0 == strncmp($str, $bom, 3)) {
                $str = substr($str, 3);
            }
            $str = str_replace("\r", '', $str);
            $enc = $this->detectEncoding($str);
            if ($enc !== 'UTF-8' && $enc !== 'ASCII') {
                $str = iconv($enc, "UTF-8", $str);
            }
            $lines = explode("\n", $str);

            $c = count($lines);
            for ($i = 0; $i < $c; $i++) {
                $line = $lines[$i];
                if (preg_match('/^[0-9]+\.(.+)/u', $line, $matches)) {
                    if (isset($matches[1])) {
                        $question = trim($matches[1]);

                        $answers = array();
                        $true = 0;

//                         Собираем ответы
                        for ($j = $i + 1; $j <= $c; $j++) { //
                            if (!isset($lines[$j])) {
                                if (count($answers)) {
                                    $questions[] = array('question' => $question, 'answers' => $answers);
                                }
                                break;
                            }
                            $line = $lines[$j];
                            if (preg_match('/^[0-9]+\.(.+)/u', $line, $matches) || $j == $c) {
                                if (count($answers)) {
                                    $questions[] = array('question' => $question, 'answers' => $answers);
                                }
                                break;
                            }
//                            echo $line;exit;
                            if (preg_match('/^\(([\?\!])\)(.+)/u', $line, $matches)) {
                                if (isset($matches[1]) && isset($matches[2])) {
                                    if ($matches[1] == '!') {
                                        $true++;
                                    }
                                    $answers[] = array('is_right' => ($matches[1] == '!'), 'answer' => trim($matches[2]));
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return new Response(Response::INTERNALSERVERERROR,
                [
                    'error' => '',
                    'code' => 'UNABLE_OPEN_FILE'
                ]
            );
        }
        if (count($questions) == 0) {
            return new Response(Response::INTERNALSERVERERROR,
                [
                    'error' => '',
                    'code' => 'NO_QUESTIONS_FOUND'
                ]
            );
        }

        foreach ($questions as $itm) {
            $countIsRight = 0;
            $question = Question::create();
            $question->site_id = 1;
            $question->body = '<p>' . strip_tags($itm['question']) . '</p>';
            $question->weight = 1;

            if (count($itm) < 2) {
                return new Response(400, ['question' => sprintf($itm['question']), 'code' => 9]);
            }

            foreach ($itm['answers'] as $i) {
                if ($i['is_right'] == 1) {
                    $countIsRight++;
                }
            }

            $question->type = ($countIsRight > 1) ? 'multi' : 'single';
            $question->allow_add_files = 0;
            $question->test_id = (int)$testId;
            $question->save();
            if (count($itm['answers']) > 0) {
                foreach ($itm['answers'] as $item) {
                    $answer = Answer::create();
                    $answer->site_id = 1;
                    $answer->question_id = (int)$question->id;
                    $answer->is_right = (int)$item['is_right'];
                    $answer->body = $item['answer'];
                    $answer->save();
                }
            }

        }

        return new Response(Response::OK, $result);
    }

    public function detectEncoding($string, $pattern_size = 50)
    {
        $list = array('CP1251', 'UTF-8', 'ASCII', '855', 'KOI8R', 'ISO-IR-111', 'CP866', 'KOI8U');
        $c = strlen($string);
        if ($c > $pattern_size) {
            $string = substr($string, floor(($c - $pattern_size) / 2), $pattern_size);
            $c = $pattern_size;
        }

        $reg1 = '/(\xE0|\xE5|\xE8|\xEE|\xF3|\xFB|\xFD|\xFE|\xFF)/i';
        $reg2 = '/(\xE1|\xE2|\xE3|\xE4|\xE6|\xE7|\xE9|\xEA|\xEB|\xEC|\xED|\xEF|\xF0|\xF1|\xF2|\xF4|\xF5|\xF6|\xF7|\xF8|\xF9|\xFA|\xFC)/i';

        $mk = 10000;
        $enc = 'ASCII';
        foreach ($list as $item) {
            $sample1 = @iconv($item, 'CP1251', $string);
            $gl = @preg_match_all($reg1, $sample1, $arr);
            $sl = @preg_match_all($reg2, $sample1, $arr);
            if (!$gl || !$sl) continue;
            $k = abs(3 - ($sl / $gl));
            $k += $c - $gl - $sl;
            if ($k < $mk) {
                $enc = $item;
                $mk = $k;
            }
        }
        return $enc;
    }


    /**
     * @method POST
     * @action deleteMulti
     * @provides application/json
     * @json
     * @return \Tonic\Response
     *
     * @api {get} /tests/:test_id/questions Масове видалення питаннь
     * @apiName deleteMulti
     * @apiGroup Tests-Question
     * @apiParam {Number} id Унікальний id тесту
     * @apiSuccess {boolean} true Повертає true
     */
    public function deleteMulti($testId)
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        foreach ($data['ids'] as $item) {
            $item = Question::getById((int)$item);
            if ($item) {
                $item->is_deleted = true;
                $item->save();
            }
        }

        return new Response(200, true);
    }
}
