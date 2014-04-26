<?php
/**
 * Course.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_courses"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $site_id
 * @property-read string $title
 * @property-read string $description
 * @property-read string $annotation
 * @property-read int $is_deleted
 * @property-read string $start_type
 * @property-read string $finish_type
 * @property-read datetime $created_at
 * @property-read datetime $updated_at
 * @property-read int $created_by
 * @property-read int $updated_by
 */
namespace Modules\Courses\Model\Base;

abstract class Course extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_courses';

    const MODEL_NAME = 'Modules\\Courses\\Model\\Course';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('title', 'varchar(255)');
        $this->hasColumn('icon', 'varchar(255)');
        $this->hasColumn('code', 'varchar(255)');
        $this->hasColumn('score_employment', 'varchar(255)');
        $this->hasColumn('finish_type', "ENUM('summary','by_test')|'summary'");
        $this->hasColumn('start_type', "ENUM('start_page','plan','elements)|'start_page'");
        $this->hasColumn('course_length', 'UN:int(10)');
        $this->hasColumn('category_id', 'UN:int(10)');
        $this->hasColumn('is_published', 'U:tinyint(1)|0');
        $this->hasColumn('description', 'N:text');
        $this->hasColumn('annotation', 'N:text');
        $this->hasColumn('registration_for_course', 'U:tinyint(1)|0');
        $this->hasColumn('is_deleted', 'U:tinyint(1)|0');
        $this->hasColumn('created_at', 'N:datetime');
        $this->hasColumn('updated_at', 'N:datetime');
        $this->hasColumn('created_by', 'UN:int(10)');
        $this->hasColumn('updated_by', 'UN:int(10)');
    }

    public function initRelations()
    {
        $this->hasRelation('Files', new \Bazalt\ORM\Relation\One2Many('Modules\\Courses\\Model\\File', 'id', 'course_id'));
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