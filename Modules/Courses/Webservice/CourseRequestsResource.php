<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseRequest;
use Modules\Tasks\Model\Task;
use Modules\Tasks\Model\TaskRefUser;

/**
 * CourseRequestsResource
 *
 * @uri /courses-requests
 */
class CourseRequestsResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @action available-courses
     * @json
     */
    public function getAvailableCourses()
    {
//        $curUser = \Bazalt\Auth::getUser();
//        if($curUser->isGuest()) {
//            return new Response(Response::FORBIDDEN, 'Permission denied');
//        }

        $collection = CourseRequest::getAvailableCourses();

        // table configuration
        $table = new \Bazalt\Rest\Collection($collection);

        $table
            ->sortableBy('id');

        //return new Response(Response::OK, $table->fetch($_GET));

        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::OK, $table->fetch($_GET));
        }

        $res = $table->fetch($_GET, function ($item) use ($curUser) {
            $item['task_id'] = TaskRefUser::getTaskIdByCourse((int)$item['id'], (int)$curUser->id);
            return $item;
        });

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @json
     */
    public function createItem()
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('course_id')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if ($curUser->isGuest()) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $course = Course::getById((int)$data['course_id']);

        $request = CourseRequest::create();
        $request->user_id = $curUser->id;
        $request->course_id = (int)$data['course_id'];
        $request->save();

        $tasks = Task::getByCourseId((int)$data['course_id']);
        if (count($tasks) > 0) {
            $task = $tasks[0];
        } else {
            $task = Task::create();
            $task->title = $course->title;
            $task->description = $course->annotation;
            $task->type = Task::TYPE_COURSE;
            $task->element_id = $course->id;
            $task->saveForCourse();
        }

        TaskRefUser::assignUser((int)$task->id, (int)$curUser->id);

        return new Response(Response::OK, $task->toArray());
    }
}
