<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;
use Modules\Courses\Model\CourseElement;
use Modules\Courses\Model\CourseTestSetting;
use Modules\Tags\Model\TagRefElement;

/**
 * CoursePlansResource
 *
 * @uri /courses/:courseId/plan
 */
class CoursePlansResource extends \Bazalt\Rest\Resource
{

    /**
     * @method GET
     * @json
     */
    public function getList($courseId)
    {
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $items = CoursePlan::getList($courseId, isset($_GET['type']) ? $_GET['type'] : null);
        $ret = [];
        foreach ($items as $item) {

            $res = [];
            $tags = TagRefElement::getElementTags($item->id, ($item->type == 'test') ? 'test' : 'resource');
            foreach ($tags as $itm) {
                $res[] = $itm->body;
            }

            $ret [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'sub_type' => $item->sub_type,
                'type' => $item->type,
                'title' => $item->title,
                'start_element' => $item->start_element,
                'tags' => implode(', ', $res)
            ];
        }
        return new Response(Response::OK, array('data' => $ret));
    }

    /**
     * @method GET
     * @action getElementsResource
     * @json
     */
    public function getElementsResource($courseId)
    {
        $data = Validator::create((array)$_GET);
        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $items = CoursePlan::getElementsList($courseId, $data['title']);
        $ret = [];
        foreach ($items as $item) {
            $res = [];
            $tags = TagRefElement::getElementTags($item->element_id, $item->type);

            foreach ($tags as $itm) {
                $res[] = $itm->body;
            }

            $ret [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'type' => $item->type,
                'sub_type' => $item->sub_type,
                'title' => $item->title,
                'tags' => implode(', ', $res)
            ];
        }

        return new Response(Response::OK, array('data' => $ret));
    }

    /**
     * @method GET
     * @action AllResources
     * @json
     */
    public function getAllResources($courseId)
    {

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $data = Validator::create((array)$_GET);
        $data->field('type')->required();
        $data->field('title');
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $collection = CoursePlan::searchResource($courseId, $data['type'], isset($data['title']) ? $data['title'] : '',
                                                    isset($data['tag']) ? $data['tag'] : '');
        $collection->limit(10);
        $collection->orderBy('title');
        $ret = [];
        $items = $collection->fetchAll();
        foreach ($items as $item) {

            $res = [];
            $tags = TagRefElement::getElementTags($item->id, ($item->type == 'test') ? 'test' : 'resource');
            foreach ($tags as $itm) {
                $res[] = $itm->body;
            }

            $ret [] = [
                'element_id' => $item->id,
                'type' => $data['type'],
                'element_type' => $item->type,
                'title' => $item->title,
                'tags' => implode(', ', $res)
            ];
        }

        return new Response(Response::OK, array('data' => $ret));
    }

    /**
     * @method POST
     * @method PUT
     * @json
     */
    public function createItem($courseId)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('element_id')->required();
        $data->field('type')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }
        $type = $data['type'];
        if ($data['type'] == 'page' || $data['type'] == 'file' || $data['type'] == 'url' || $data['type'] == 'html') {
            $data['type'] = 'resource';
        }

        if (!CoursePlan::getElement((int)$data['element_id'], $data['type'], $courseId)) {
            $courseEl = CourseElement::create($courseId);
            $courseEl->element_id = (int)$data['element_id'];
            $courseEl->type = $data['type'];
            $courseEl->order = count(CourseElement::getList($courseId));
            $courseEl->save();
        }

        $plan = CoursePlan::create($courseId);
        $plan->element_id = (int)$data['element_id'];
        $plan->type = $data['type'];
        $plan->order = count(CoursePlan::getList($courseId));
        $plan->save();

        if($type == 'test') {
            $taskSett = CourseTestSetting::create((int)$courseId, (int)$plan->element_id);
            $taskSett->all_questions = true;
            $taskSett->unlim_attempts = true;
            $taskSett->save();
        }

        $res = $plan->toArray();
        $res['sub_type'] = $type;

        return new Response(Response::OK, $res);
    }

    /**
     * @method PUT
     * @action resort
     * @json
     */
    public function resort($courseId)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('orders');

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $items = [];
        $orders = (array)$data['orders'];
        asort($orders);
        foreach ($orders as $id => $order) {
            $planItem = CoursePlan::getById((int)$id);
            $planItem->order = $order;
            $planItem->save();
            $items []= $planItem;
        }
        CoursePlan::resortingTasks($items);

        return new Response(Response::OK, true);
    }

    /**
     * @method PUT
     * @action changeStartElement
     * @json
     */
    public function changeStartElement($courseId)
    {
        $data = Validator::create((array)$this->request->data);

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        CoursePlan::setStartElement($courseId, $data['id']);

        return new Response(Response::OK, true);
    }

}
