<?php
namespace Modules\Courses\Model;

use Bazalt\ORM;
use Modules\Tasks\Model\Task;

class CourseRequest extends Base\CourseRequest
{
    public static function getCollection()
    {
        $q = CourseRequest::select();
        return new \Bazalt\ORM\Collection($q);
    }

    public static function getAvailableCourses()
    {
        $q = ORM::select('Modules\\Courses\\Model\\Course c', 'c.*')
            ->where('c.is_deleted != ?', 1)
            ->andWhere('c.is_published = ?', 1);

        return new \Bazalt\ORM\Collection($q);
    }

    public static function create()
    {
        $o = new CourseRequest();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }
}
