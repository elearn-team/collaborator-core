<?php

namespace Modules\Courses\Model\Base;

abstract class File extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_courses_files';

    const MODEL_NAME = 'Modules\Courses\Model\File';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('course_id', 'U:int(10)');
        $this->hasColumn('name', 'N:varchar(255)');
        $this->hasColumn('title', 'N:varchar(255)');
        $this->hasColumn('url', 'N:varchar(255)');
        $this->hasColumn('size', 'U:int(10)');
        $this->hasColumn('sort_order', 'U:int(10)');
    }

    public function initRelations()
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