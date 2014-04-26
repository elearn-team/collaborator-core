<?php

namespace Modules\Tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\File;
use Modules\Tests\Model\Answer;

/**
 * QuestionResource
 *
 * @uri /tests/questions/:id
 *
 * @apiDefineSuccessStructure QuestionToArrayStructure
 * @apiSuccess (200) {Number} id Унікальний id питання
 * @apiSuccess (200) {Number} test_id Унікальний id тесту
 * @apiSuccess (200) {String} type Тип питання
 * @apiSuccess (200) {Number} weight Вага питання
 * @apiSuccess (200) {String} body Текст питання
 * @apiSuccess (200) {Boolean} allow_add_files Можливість додавати файли до відповіді
 * @apiSuccess (200) {Date} created_at Дата створення
 * @apiSuccess (200) {Date} updated_at Дата останнього редагування
 * @apiSuccess (200) {Object[]} files Файли питання
 * @apiSuccess (200) {Number} files.question_id Унікальний id питання
 * @apiSuccess (200) {String} files.name Назва файлу
 * @apiSuccess (200) {String} files.file Шлях до файлу
 * @apiSuccess (200) {Date} files.created_at Дата створення файлу
 * @apiSuccess (200) {Date} files.updated_at Дата останнього редагування файлу
 */
class QuestionResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests/questions/:id Отримати питання по id
     * @apiName getItem
     * @apiGroup Tests-Question
     * @apiParam {Number} id Унікальний id питання
     * @apiSuccessStructure QuestionToArrayStructure
     */
    public function getItem($id)
    {
        $question = Question::getById($id);
        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $id)]);
        }
        $res = $question->toArray();
        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     *
     * @api {post} /tests/questions/:id Збереження питання
     * @apiName saveItem
     * @apiGroup Tests-Question
     * @apiParam {Number} id Унікальний id питання
     * @apiSuccessStructure QuestionToArrayStructure
     */
    public function saveItem($id = null)
    {
        $data = Validator::create((array)$this->request->data);
        if ($id) {
            $data->field('id')->required();
        }
        $data->field('weight')->required();
        $data->field('type')->required();
        $data->field('test_id')->required();
        $data->field('body')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        if ($id) {
            $question = Question::getById($id);
        } else {
            $question = Question::create();
        }
        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $question->body = $data['body'];
        $question->weight = $data['weight'];
        $question->type = $data['type'];
        $question->allow_add_files = ($data['allow_add_files']) ? $data['allow_add_files'] : 0;
        $question->test_id = (int)$data['test_id'];
        $question->save();
        //files
        $ids = [];
        if ($data['files']) {
            foreach ($data['files'] as $fileData) {
                $fileArr = (array)$fileData;
                if (isset($fileArr['error'])) {
                    continue;
                }
                $file = isset($fileArr['id']) ? File::getById((int)$fileArr['id']) : File::create();
                $file->name = $fileArr['name'];
                $file->file = $fileArr['file'];
                $file->save();

                $question->Files->add($file);
                $ids [] = $file->id;
            }

        }
        $question->Files->clearRelations($ids);

        $res = $question->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     *
     * @api {delete} /tests/questions/:id Видалення питання
     * @apiName deleteItem
     * @apiGroup Tests-Question
     * @apiParam {Number} id Унікальний id питання
     * @apiSuccess {Boolean} true Повертає true
     */
    public function deleteItem($id)
    {
        $item = Question::getById((int)$id);

        if (!$item) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $id)]);
        }
        $item->is_deleted = true;
        $item->save();
        return new Response(200, true);
    }


    /**
     * @method PUT
     * @action checkAnswer
     * @json
     *
     * @api {delete} /tests/questions/:id Перевірка відповідей
     * @apiName checkAnswer
     * @apiGroup Tests-Question
     * @apiParam {Number} id Унікальний id питання
     * @apiSuccess {Object[]} data Массив з позначенимим правильними відповідями
     * @apiSuccess {Number} data.id Унікальний id відповіді
     * @apiSuccess {Number} data.is_right Правльність відповіді (якщо правильна 1 інакше 0)
     */
    public function checkAnswer($id)
    {
        $data = Validator::create((array)$this->request->data);
        $answers = Answer::getCollection($id);

        $res = array();
        foreach ($data['answers'] as $item) {

            if (isset($item->checked) && $item->checked == true) {
                foreach ($answers->fetchAll() as $i) {
                    if ($item->id == $i->id) {
                        $res[] = array('id' => $i->id, 'is_right' => $i->is_right);
                    }
                }
            }
        }
        return new Response(Response::OK, array('data' => $res));
    }

    /**
     * @method POST
     * @action duplicateQuestion
     * @json
     *
     * @api {post} /tests/questions/:id Створення копії питання
     * @apiName duplicateQuestion
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id питання
     * @apiSuccess {Boolean} true Повертає true
     */
    public function duplicateQuestion($id)
    {
        $data = Validator::create((array)$this->request->data);
//        $data->field('test_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $question = Question::getById($id);

        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $newQuestion = new Question();
        $newQuestion->site_id = $question->site_id;
        $newQuestion->test_id = $data['test_id'];
        $newQuestion->type = $question->type;
        $newQuestion->body = $question->body;

        $newQuestion->weight = $question->weight;
        $newQuestion->save();

        $answers = Answer::getCollection($question->id)->fetchAll();
        if (count($answers) > 0) {
            foreach ($answers as $answer) {
                $a = new Answer();
                $a->site_id = $answer->site_id;
                $a->question_id = $newQuestion->id;
                $a->is_right = $answer->is_right;
                $a->body = $answer->body;
                $a->save();
            }
        }

        $files = $question->Files->get();

        if (count($files) > 0) {
            foreach ($files as $file) {
                $f = new File();
                $f->site_id = $file->site_id;
                $f->question_id = $newQuestion->id;
                $f->name = $file->name;
                $f->file = $file->file;
                $f->save();
            }
        }

        return new Response(200, true);
    }
}
