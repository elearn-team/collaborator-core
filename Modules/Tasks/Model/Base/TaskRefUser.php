<?php
/**
 * TaskRefUser.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_tasks_ref_users"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $task_id
 * @property-read int $user_id
 */
namespace Modules\Tasks\Model\Base;

abstract class TaskRefUser extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tasks_ref_users';

    const MODEL_NAME = 'Modules\\Tasks\\Model\\TaskRefUser';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PU:int(10)');
        $this->hasColumn('task_id', 'U:int(10)');
        $this->hasColumn('user_id', 'U:int(10)');
        $this->hasColumn('status',  "ENUM('started','inprogress','finished', 'verification', 'fail')|'started'");
        $this->hasColumn('attempts_limit', 'U:int(10)|0');
        $this->hasColumn('attempts_count', 'U:int(10)|0');
        $this->hasColumn('mark', 'U:float(10)');
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