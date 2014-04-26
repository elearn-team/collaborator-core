<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseElement;

/**
 * CourseElementResource
 *
 * @uri /courses/:courseId/elements/:elementId
 */
class CourseElementResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function getItem($courseId, $id)
    {
        $item = CourseElement::getById($id);
        if (!$item) {
            return new Response(400, ['id' => sprintf('Course element "%s" not found', $id)]);
        }

        return new Response(Response::OK, $item->getObject()->toArray());
    }


    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteItem($courseId, $id)
    {
        $item = CourseElement::getById((int)$id);
        if (!$item) {
            return new Response(400, ['id' => sprintf('Course element "%s" not found', $id)]);
        }
        $item->delete();
        return new Response(200, true);
    }
}
