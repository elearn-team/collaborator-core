<?php

namespace Components\Tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Result;
use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;
use Modules\Tasks\Model\Task;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;
use Modules\Tests\Model\File;

/**
 * TestResource
 *
 * @uri /tests/:id
 *
 * @apiDefineSuccessStructure TestStructure
 * @apiSuccess {Number} id Унікальний id
 * @apiSuccess {String} title Назва тесту
 * @apiSuccess {String} description Опис
 * @apiSuccess {Date} created_at Дата створення
 * @apiSuccess {Date} updated_at Дата останнього редагування
 */
class TestResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests/:id Отримати тест по id
     * @apiName getItem
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id тесту
     *
     * @apiSuccessStructure TestStructure
     * @apiSuccess {String} questions_count  Кількість питань
     * @apiSuccess {String[]} tags Теги
     */
    public function getItem($id)
    {
        $test = Test::getById($id);
        if (!$test) {
            return new Response(400, ['id' => sprintf('Test "%s" not found', $id)]);
        }
        $res = $test->toArray();
        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     *
     * @api {post} /tests/:id Збереження тесту
     * @apiName saveItem
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id тесту
     * @apiSuccessStructure TestStructure
     * @apiSuccess {String} questions_count  Кількість питань
     * @apiSuccess {String[]} tags Теги
     */
    public function saveItem($id)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        $data->field('title')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $test = Test::getById($id);
        if (!$test) {
            return new Response(400, ['id' => sprintf('Test "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $test->title = $data['title'];
        $test->description = isset($data['description']) ? $data['description'] : '';
        $test->save();

        TagRefElement::clearTags($test->id, Task::TYPE_TEST);
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $itm) {
                Tag::addTag($test->id, Task::TYPE_TEST, $itm);
            }
        }

        $res = $test->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     *
     * @api {delete} /tests/:id Видалення тесту
     * @apiName deleteItem
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id тесту
     * @apiSuccess {Boolean} true Повертає true
     */
    public function deleteItem($id)
    {
        $item = Test::getById((int)$id);

        if (!$item) {
            return new Response(400, ['id' => sprintf('Test "%s" not found', $id)]);
        }
        $item->is_deleted = true;
        $item->save();
        return new Response(200, true);
    }

    /**
     * @method POST
     * @action duplicateTest
     * @json
     *
     * @api {post} /tests/:id Створення копії тесту
     * @apiName duplicateTest
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id тесту
     * @apiSuccess {Boolean} true Повертає true
     */
    public function duplicateTest($id)
    {
        $test = Test::getById($id);

        if (!$test) {
            return new Response(400, ['id' => sprintf('Test "%s" not found', $id)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $newTest = new Test();
        $newTest->site_id = $test->site_id;
        $newTest->title = $test->title . ' (Копия)';
        $newTest->description = $test->description;
        $newTest->save();

        $questions = Question::getCollection($id)->fetchAll();

        if (count($questions) > 0) {
            foreach ($questions as $question) {
                $q = new Question();
                $q->test_id = $newTest->id;
                $q->site_id = $question->site_id;
                $q->type = $question->type;
                $q->body = $question->body;
                $q->weight = $question->weight;
                $q->save();

                $answers = Answer::getCollection($question->id)->fetchAll();
                if (count($answers) > 0) {
                    foreach ($answers as $answer) {
                        $a = new Answer();
                        $a->site_id = $answer->site_id;
                        $a->question_id = $q->id;
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
                        $f->question_id = $q->id;
                        $f->name = $file->name;
                        $f->file = $file->file;
                        $f->save();
                    }
                }

            }
        }
        $tags = TagRefElement::getElementTags($test->id, Task::TYPE_TEST);
        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                Tag::addTag($newTest->id, Task::TYPE_TEST, $tag->body);
            }
        }

        return new Response(200, true);
    }

    /**
     * @method GET
     * @action getReportByResultId
     * @json
     *
     * @api {get} /tests/:id Отримати звіт по id результату
     * @apiName getReportByResultId
     * @apiGroup Tests-Test
     * @apiParam {Number} id Унікальний id тесту
     * @apiSuccess {Object[]} data Звіт по id результату
     * @apiSuccess {Object[]} data.settings Налаштування завдання
     * @apiSuccess {Number} data.settings.task_id Унікальний id завдання
     * @apiSuccess {Number} data.settings.all_question Усі питання в тесті
     * @apiSuccess {Number} data.settings.question_count Кількість питання які будуть у тесті
     * @apiSuccess {Number} data.settings.unlim_attempts Необмежені спроби тестування
     * @apiSuccess {Number} data.settings.attempts_count Кількість спроб тестування
     * @apiSuccess {Number} data.settings.training Режим тренування
     * @apiSuccess {Date} data.settings.created_at Дата створення
     * @apiSuccess {Date} data.settings.updated_at Дата останнього редагування
     * @apiSuccess {Number} data.settings.time Кількість часу на проходження тесту(хв.) Якщо 0 то необмежено
     * @apiSuccess {Object[]} data.task_params Параметри результату
     * @apiSuccess {String} data.task_params.user_full_name ФІО користувача
     * @apiSuccess {Date} data.task_params.task_start_date Дата початку тестування
     * @apiSuccess {Date} data.task_params.task_finish_date Дата закінчення тестування
     * @apiSuccess {String} data.task_params.task_status Статус завдання
     * @apiSuccess {Number} data.task_params.task_mark Оцінка завдання
     * @apiSuccess {Number} data.task_params.task_attempts_limit Кількість дозволених спроб користувача
     * @apiSuccess {Number} data.task_params.user_id Унікальний id користувача
     * @apiSuccess {Number} data.task_params.task_attempt_number Кількість використаних спроб
     * @apiSuccess {Number} data.task_params.user_photo_thumb Фото користувача
     * @apiSuccess {Object[]} data.questions Питання тесту
     * @apiSuccess {String} data.questions.body Текст питання
     * @apiSuccess {String} data.questions.type Тип питання
     * @apiSuccess {Number} data.questions.weight Вага питання
     * @apiSuccess {Number} data.questions.mark Оцінка питання
     * @apiSuccess {Number} data.questions.result_id Унікальний id результату
     * @apiSuccess {Number} data.questions.task_id Унікальний id завдання
     * @apiSuccess {Object[]} data.questions.answers Відповіді
     * @apiSuccess {Number} data.questions.answers.id Унікальний id відповіді
     * @apiSuccess {String} data.questions.answers.text_answer Текст відповіді
     * @apiSuccess {Number} data.questions.answers.mark Оцінка відповіді
     * @apiSuccess {Object[]} data.questions.answers.files Файли прикріплені до відповіді
     * @apiSuccess {String} data.questions.answers.files.name Назва файлу
     * @apiSuccess {String} data.questions.answers.files.url Шлях до файлу
     * @apiSuccess {String} data.questions.answers.files.extension Розширення файлу
     */
    public function getReportByResultId($id)
    {
        $data = Validator::create((array)$_GET);
        $data->field('resultId')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $test = Test::getById($id);

        if (!$test) {
            return new Response(400, ['id' => sprintf('Test "%s" not found', $id)]);
        }
        $res = [];
        if ($data['resultId'] && $data['resultId'] != 0) {

            $res = $test->getReport((int)$data['resultId']);
        }

        return new Response(Response::OK, ['data' => $res]);
    }
}
