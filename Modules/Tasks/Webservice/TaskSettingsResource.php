<?php

namespace Modules\Tasks\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tests\Model\Test;
use Modules\Resources\Model\Page;
use Modules\Courses\Model\Course;
use Modules\Tasks\Model\Task;

/**
 * TaskSettingsResource
 *
 * @uri /task-settings
 */
class TaskSettingsResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @action getTests
     * @json
     */
    public function getTests()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $res = [];
        $tests = Test::getCollection();
        $tests = $tests->fetchAll();
        foreach ($tests as $test) {
            $res [] = [
                'id' => (int)$test->id,
                'title' => $test->title,
                'questions_count' => $test->getAllQuestionsCount()
            ];
        }
        return new Response(Response::OK, array('data' => $res));
    }

    /**
     * @method GET
     * @action getResources
     * @json
     */
    public function getResources()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $res = [];
        $resources = Page::getCollection(true);
        $resources = $resources->fetchAll();
        foreach ($resources as $resource) {
            $res [] = [
                'id' => (int)$resource->id,
                'title' => $resource->title,
                'type' => $resource->type
            ];
        }
        return new Response(Response::OK, array('data' => $res));
    }

    /**
     * @method GET
     * @action getCourses
     * @json
     */
    public function getCourses()
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('tasks.can_manage_tasks')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $res = [];
        $resources = Course::getCollection();
        $resources = $resources->fetchAll();
        foreach ($resources as $resource) {
            $res [] = [
                'id' => (int)$resource->id,
                'title' => $resource->title
            ];
        }
        return new Response(Response::OK, array('data' => $res));
    }
}
