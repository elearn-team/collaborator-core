<?php

namespace Modules\Courses\Model\Base;
use \Bazalt\Site;

abstract class Category extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_courses_categories';

    const MODEL_NAME = 'Modules\\Courses\\Model\\CourseCategory';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('title', 'varchar(255)');
        $this->hasColumn('url', 'varchar(255)');
        $this->hasColumn('image', 'varchar(255)');
        $this->hasColumn('is_hidden', 'U:tinyint(1)|0');
        $this->hasColumn('is_published', 'U:tinyint(1)');
    }

    public function initRelations()
    {
        $this->hasRelation('Elements', new \Bazalt\ORM\Relation\NestedSet('Modules\\Courses\\Model\\Category', 'site_id'));
        $this->hasRelation('PublicElements',
            new \Bazalt\ORM\Relation\NestedSet('Modules\\Courses\\Model\\Category', 'site_id', null, ['is_hidden' => '0', 'is_published' => 1]));
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