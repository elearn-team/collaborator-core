<?php
/**
 * CourseTestSetting.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_courses_test_setting"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $task_id
 * @property-read int $test_id
 * @property-read bool $all_questions
 * @property-read int $questions_count
 * @property-read bool $unlim_attempts
 * @property-read int $attempts_count
 */
namespace Modules\Courses\Model\Base;

abstract class CourseTestSetting extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_courses_test_setting';

    const MODEL_NAME = 'Modules\\Courses\\Model\\CourseTestSetting';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('course_id', 'U:int(10)');
        $this->hasColumn('test_id', 'U:int(10)');
        $this->hasColumn('all_questions', 'U:tinyint(1)|0');
        $this->hasColumn('questions_count', 'U:int(10)');
        $this->hasColumn('unlim_attempts', 'U:tinyint(1)|0');
        $this->hasColumn('attempts_count', 'U:int(10)');
        $this->hasColumn('threshold', 'U:int(3)');
        $this->hasColumn('training', 'U:tinyint(1)|0');
        $this->hasColumn('time', 'UN:int(10)');
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