<?php
/**
 * Task.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_tasks"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $site_id
 * @property-read string $title
 * @property-read int $type
 * @property-read int $element_id
 * @property-read int $parent_id
 * @property-read int $threshold
 * @property-read int $is_deleted
 * @property-read datetime $created_at
 * @property-read datetime $updated_at
 * @property-read int $created_by
 * @property-read int $updated_by
 */
namespace Modules\Tasks\Model\Base;

abstract class Task extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tasks';

    const MODEL_NAME = 'Modules\Tasks\Model\Task';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('parent_id', 'UN:int(10)');
        $this->hasColumn('element_id', 'U:int(10)');
        $this->hasColumn('threshold', 'U:int(3)');
        $this->hasColumn('type', 'varchar(255)');
        $this->hasColumn('description', 'N:text');
        $this->hasColumn('title', 'varchar(255)');
        $this->hasColumn('is_deleted', 'U:tinyint(1)|0');
        $this->hasColumn('created_at', 'N:datetime');
        $this->hasColumn('updated_at', 'N:datetime');
        $this->hasColumn('created_by', 'UN:int(10)');
        $this->hasColumn('updated_by', 'UN:int(10)');
    }

    public function initRelations()
    {
        $this->hasRelation('Elements', new \Bazalt\ORM\Relation\NestedSet('Modules\\Tasks\\Model\\Task', 'parent_id'));
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