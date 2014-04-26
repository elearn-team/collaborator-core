<?php

namespace Modules\Tasks\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Resources\Model\Page;
use Modules\Courses\Model\Course;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskTestSetting;

/**
 * TaskSettingResource
 *
 * @uri /task-settings/:id
 */
class TaskSettingResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItem($id)
    {
        $task = Task::getById($id);

        if($task->type == Task::TYPE_TEST) {
            $taskSett = TaskTestSetting::getByTaskId((int)$id);
            if(!$taskSett) {
                $taskSett = TaskTestSetting::create((int)$id);
            }
            return new Response(Response::OK, $taskSett->toArray());
        }

        return new Response(Response::OK, []);
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function saveItem($id)
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $task = Task::getById($id);
        if (!$task) {
            return new Response(400, ['id' => sprintf('Task "%s" not found', $id)]);
        }

        $taskSett = TaskTestSetting::getByTaskId((int)$id);
        if(!$taskSett) {
            $taskSett = TaskTestSetting::create((int)$id);
        }

        $taskSett->all_questions = false;
        if($data['all_questions'] && $data['all_questions'] != null){
            $taskSett->all_questions = true;
        }

        if($data['questions_count'] && $data['questions_count'] != null){
            $taskSett->questions_count = (int)$data['questions_count'];
        }

        $taskSett->unlim_attempts = false;
        if($data['unlim_attempts'] && $data['unlim_attempts'] != null){
            $taskSett->unlim_attempts = true;
        }

        if($data['attempts_count'] && $data['attempts_count'] != null){
            $taskSett->attempts_count = (int)$data['attempts_count'];
        }

        if($data['time_type'] && $data['time_type'] == 'unlimited'){
            $taskSett->time = null;
        }

        if($data['time_type'] && $data['time_type'] == 'limited'){
            $taskSett->time = (int)$data['time'];
        }

        if($data['training']){
            $taskSett->training = 1;
        }else{
            $taskSett->training = null;
        }
        $taskSett->save();

        $task->save();

        return new Response(Response::OK, $taskSett->toArray());
    }
}
