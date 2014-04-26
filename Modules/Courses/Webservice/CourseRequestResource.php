<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CourseRequest;

/**
 * CourseElementResource
 *
 * @uri /courses/:courseId/requests/:elementId
 */
class CourseRequestResource extends \Bazalt\Rest\Resource
{
    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteItem($courseId, $id)
    {
        $item = CourseRequest::getById((int)$id);
        if (!$item) {
            return new Response(400, ['id' => sprintf('Course request "%s" not found', $id)]);
        }
        $item->delete();
        return new Response(200, true);
    }
}
