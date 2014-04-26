<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;
use Modules\Tags\Model\TagRefElement;

/**
 * CourseElementsResource
 *
 * @uri /courses/:courseId/elements
 */
class CourseElementsResource extends \Bazalt\Rest\Resource
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

        $items = CourseElement::getList($courseId);
        $ret = [];
        foreach ($items as $item) {
            $ret [] = [
                'id' => $item->id,
                'element_id' => $item->element_id,
                'type' => $item->type,
                'sub_type' => $item->sub_type,
                'title' => $item->title,
                'code' => $item->code,
                'description' => $item->description
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
        $data->field('categoryId');
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }
        $collection = CourseElement::searchResource($courseId, $data['type'],
            isset($data['title']) ? $data['title'] : '', isset($data['categoryId']) ? $data['categoryId']: null,
            isset($data['tag']) ? $data['tag']: null);
        $collection->limit(10);
        $collection->orderBy('title');

        $ret = [];
        $items = $collection->fetchAll();
        foreach ($items as $key => $item) {
            $res = [];
            $tags = TagRefElement::getElementTags($item->id, ($item->type == 'test') ? 'test' : 'resource');
            foreach ($tags as $itm) {
                $res[] = $itm->body;
            }

            $ret [] = [
                'id' => $item->id,
                'title' => $item->title,
                'code' => $item->code,
                'description' => $item->description,
                'tags' => implode(', ', $res)
            ];

            if($data['type'] === 'All'){
               $ret[$key]['type'] = $item->type;
            }else{
               $ret[$key]['type'] = $data['type'];
            }
        }
        return new Response(Response::OK, array('data' => $ret));
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

        foreach ($data['orders'] as $id => $order) {
            CourseElement::resorting($id, $order);
        }

        return new Response(Response::OK, true);
    }

    /**
     * @method POST
     * @json
     */
    public function createItem($courseId)
    {
        $data = Validator::create((array)$this->request->data);
        $data->field('id')->required();
        $data->field('type')->required();
        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        $curUser = \Bazalt\Auth::getUser();
        if (!$curUser->hasPermission('courses.can_manage_courses')) {
            return new Response(Response::FORBIDDEN, 'Permission denied');
        }

        $courseEl = CourseElement::create($courseId);
        $courseEl->element_id = (int)$data['id'];
        if ($data['type'] == 'page' || $data['type'] == 'file' || $data['type'] == 'url' || $data['type'] == 'html') {
            $courseEl->type = 'resource';
        } else {
            $courseEl->type = $data['type'];
        }

        $courseEl->order = count(CourseElement::getList($courseId));
        $courseEl->save();

        $res = $courseEl->toArray();
        $res['sub_type'] = $data['type'];

        return new Response(Response::OK, $res);
    }
}
