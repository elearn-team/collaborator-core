<?php

namespace Modules\Courses\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Courses\Model\Course;
use Modules\Courses\Model\CoursePlan;

/**
 * CoursePlanResource
 *
 * @uri /courses/:courseId/plan/:id
 */
class CoursePlanResource extends \Bazalt\Rest\Resource
{
    /**
     * @method DELETE
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function deleteItem($courseId, $id)
    {
        $item = CoursePlan::getById((int)$id);
        if (!$item) {
            return new Response(400, ['id' => sprintf('CourseElement "%s" not found', $id)]);
        }
        $item->delete();
        return new Response(200, true);
    }
}
