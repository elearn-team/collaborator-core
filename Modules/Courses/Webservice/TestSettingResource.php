<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\CourseTestSetting;
use Modules\Tests\Model\Test;

/**
 * TestSettingResource
 *
 * @uri /courses/:courseId/test/:testId
 */
class TestSettingResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItem($courseId, $testId)
    {
        $taskSett = CourseTestSetting::getByCourseId((int)$courseId, (int)$testId);
        if (!$taskSett) {
            $taskSett = CourseTestSetting::create((int)$courseId, (int)$testId);
        }
        $test = Test::getById((int)$testId);
        $testArr = $test->toArray();
        $testArr['questions_count'] = $test->getAllQuestionsCount();
        $ret = [
            'test' => $testArr,
            'setting' => $taskSett->toArray()
        ];

        return new Response(Response::OK, $ret);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function saveItem($courseId, $testId)
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $taskSett = CourseTestSetting::getByCourseId((int)$courseId, (int)$testId);
        if (!$taskSett) {
            $taskSett = CourseTestSetting::create((int)$courseId, (int)$testId);
        }

        $taskSett->all_questions = false;
        if ($data['all_questions'] && $data['all_questions'] != null) {
            $taskSett->all_questions = true;
        }

        if ($data['questions_count'] && $data['questions_count'] != null) {
            $taskSett->questions_count = (int)$data['questions_count'];
        }

        $taskSett->unlim_attempts = false;
        if ($data['unlim_attempts'] && $data['unlim_attempts'] != null) {
            $taskSett->unlim_attempts = true;
        }

        if ($data['attempts_count'] && $data['attempts_count'] != null) {
            $taskSett->attempts_count = (int)$data['attempts_count'];
        }

        if ($data['time_type'] && $data['time_type'] == 'unlimited') {
            $taskSett->time = null;
        }

        if ($data['time_type'] && $data['time_type'] == 'limited') {
            $taskSett->time = (int)$data['time'];
        }

        if (isset($data['threshold'])) {
            $taskSett->threshold = (int)$data['threshold'];
        }

        if ($data['training']) {
            $taskSett->training = 1;
        } else {
            $taskSett->training = null;
        }

        $taskSett->save();

        return new Response(Response::OK, $taskSett->toArray());
    }
}
