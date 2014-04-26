<?php
/**
 * CourseRequest.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_courses_requests"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $site_id
 * @property-read int $user_id
 * @property-read int $course_id
 * @property-read datetime $created_at
 * @property-read datetime $updated_at
 * @property-read int $created_by
 * @property-read int $updated_by
 */
namespace Modules\Courses\Model\Base;

abstract class CourseRequest extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_courses_requests';

    const MODEL_NAME = 'Modules\\Courses\\Model\\CourseRequest';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('user_id', 'U:int(10)');
        $this->hasColumn('course_id', 'U:int(10)');
        $this->hasColumn('created_at', 'N:datetime');
        $this->hasColumn('updated_at', 'N:datetime');
        $this->hasColumn('created_by', 'UN:int(10)');
        $this->hasColumn('updated_by', 'UN:int(10)');
    }

    public function initRelations()
    {
    }

    public function initPlugins()
    {
        $this->hasPlugin('Bazalt\ORM\Plugin\Timestampable', array(
            'created' => 'created_at',
            'updated' => 'updated_at',
        ));
        $this->hasPlugin('Bazalt\Auth\ORM\Author', array(
            'created_by' => 'created_by',
            'updated_by' => 'updated_by',
        ));
    }

}