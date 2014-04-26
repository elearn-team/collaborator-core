<?php

namespace Modules\Tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;

/**
 * AnswersResource
 *
 * @uri /tests/:question_id/answers
 */
class AnswersResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests/:question_id/answers Отримати всі відповіді по id питання
     * @apiName getList
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiSuccess (200) {Number} question_id Унікальний id питання
     * @apiSuccess (200) {Number} is_right Правильність відповіді
     * @apiSuccess (200) {Text} body Питання
     * @apiSuccess (200) {Date} created_at Дата створення
     * @apiSuccess (200){Date} updated_at Дата останнього редагування
     *
     */
    public function getList($questionId)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $collection = Answer::getCollection($questionId);

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);
        $table
            ->sortableBy('id')
            ->sortableBy('type')
            ->sortableBy('body')

            ->filterBy('id', function ($collection, $columnName, $value) {
                $collection->andWhere('id = ?', (int)$value);
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
     * @api {post} /tests/:question_id/answers Створеня відповіді
     * @apiName createItem
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiSuccessStructure AnswerToArrayStructure
     */
    public function createItem($questionId)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('body')->required();
        $data->field('question_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $answer = Answer::create();
        $answer->body = $data['body'];
        $answer->question_id = (int)$data['question_id'];
//        print_r($answer);exit;
        $answer->save();

        $res = $answer->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @action set-all-false
     * @json
     *
     * @api {post} /tests/:question_id Установити всі відповіді неправильними
     * @apiName setAllFalse
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiSuccess {Object[]} data Відповіді
     * @apiSuccess {Number} data.id Унікальний id відповіді
     * @apiSuccess {Number} data.question_id Унікальний id питання
     * @apiSuccess {Boolean} data.is_right Правильна відповідь
     * @apiSuccess {String} data.body Текст відповіді
     * @apiSuccess {Date} data.created_at Дата створення відповіді
     * @apiSuccess {Date} data.updated_at Дата останнього редагування відповіді
     */
    public function setAllFalse($questionId)
    {
        $question = Question::getById($questionId);
        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $questionId)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $res = [];
        $answers = $question->Answers->get();
        if ($answers) {
            foreach ($answers as $answr) {
                $answr->is_right = false;
                $answr->save();
            }
            foreach ($answers as $answer) {
                $res[] = $answer->toArray();
            }
        }

        return new Response(Response::OK, ['data' => $res]);
    }
}
