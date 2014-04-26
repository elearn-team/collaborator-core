<?php

namespace Components\Tests\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Tests\Model\Question;
use Modules\Tests\Model\Answer;

/**
 * AnswerResource
 *
 * @uri /tests/:question_id/answers/:answer_id
 *
 * @apiDefineSuccessStructure AnswerToArrayStructure
 * @apiSuccess {Number} id Унікальний id відповіді
 * @apiSuccess {Number} question_id Унікальний id питання
 * @apiSuccess {Boolean} is_right Правильна відповідь
 * @apiSuccess {String} body Текст відповіді
 * @apiSuccess {Date} created_at Дата створення відповіді
 * @apiSuccess {Date} updated_at Дата останнього редагування відповіді
 */
class AnswerResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     *
     * @api {get} /tests/:question_id/answers/:answer_id Отримати питання по id
     * @apiName getItem
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiParam {Number} answer_id Унікальний id відповіді
     * @apiSuccessStructure AnswerToArrayStructure
     */
    public function getItem($questionId, $answerId)
    {
        $answer = Answer::getById($answerId);
        if (!$answer) {
            return new Response(400, ['id' => sprintf('Answer "%s" not found', $answerId)]);
        }
        $res = $answer->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     *
     * @api {post} /tests/:question_id/answers/:answer_id Збереження відповіді
     * @apiName saveItem
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiParam {Number} answer_id Унікальний id відповіді
     * @apiSuccessStructure AnswerToArrayStructure
     */
    public function saveItem($questionId, $answerId)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        $data->field('body')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $answer = Answer::getById($answerId);
        if (!$answer) {
            return new Response(400, ['id' => sprintf('Answer "%s" not found', $answerId)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $answer->body = $data['body'];
        $answer->save();

        $res = $answer->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method POST
     * @action set-is-right
     * @json
     *      *
     * @api {post} /tests/:question_id/answers/:answer_id Позначення питання як правильне
     * @apiName setIsRight
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiParam {Number} answer_id Унікальний id відповіді
     * @apiSuccessStructure AnswerToArrayStructure
     */
    public function setIsRight($questionId, $answerId)
    {
        $answer = Answer::getById($answerId);
        if (!$answer) {
            return new Response(400, ['id' => sprintf('Answer "%s" not found', $answerId)]);
        }

        $question = Question::getById($questionId);
        if (!$question) {
            return new Response(400, ['id' => sprintf('Question "%s" not found', $questionId)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        switch ($question->type) {
            case Question::TYPE_SINGLE_ANSWER:
                $answers = $question->Answers->get();
                foreach ($answers as $answr) {
                    $answr->is_right = false;
                    $answr->save();
                }

                $answer->is_right = true;
                $answer->save();
                break;
            case Question::TYPE_MULTI_ANSWER:
                $answer->is_right = !(bool)$answer->is_right;
                $answer->save();
                break;
            default:
                return new Response(400, ['type' => sprintf('Unknown question type "%s"', $question->type)]);
        }

        $res = $answer->toArray();

        return new Response(Response::OK, $res);
    }

    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     *
     * @api {delete} /tests/:question_id/answers/:answer_id Видалення відповіді
     * @apiName deleteItem
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiParam {Number} answer_id Унікальний id відповіді
     * @apiSuccess {Boolean} true Повертає true
     */
    public function deleteItem($questionId, $answerId)
    {
        $item = Answer::getById((int)$answerId);

        if (!$item) {
            return new Response(400, ['id' => sprintf('Answer "%s" not found', $answerId)]);
        }
        $item->is_deleted = true;
        $item->save();
        return new Response(200, true);
    }

    /**
     * @method POST
     * @action duplicateAnswer
     * @json
     *
     * @api {delete} /tests/:question_id/answers/:answer_id Створення копії відповіді
     * @apiName duplicateQuestion
     * @apiGroup Tests-Answer
     * @apiParam {Number} question_id Унікальний id питання
     * @apiParam {Number} answer_id Унікальний id відповіді
     * @apiSuccess {Boolean} true Повертає true
     */
    public function duplicateAnswer($questionId, $answerId)
    {
        $answer = Answer::getById((int)$answerId);

        if (!$answer) {
            return new Response(400, ['id' => sprintf('Answer "%s" not found', $answerId)]);
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tests.can_manage_tests')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $newAnswer = new Answer();
        $newAnswer->site_id = $answer->site_id;
        $newAnswer->question_id = (int)$questionId;
        $newAnswer->is_right = $answer->is_right;
        $newAnswer->body = $answer->body;
        $newAnswer->save();


        return new Response(200, true);
    }
}
