<?php

namespace Modules\Tags\Model\Base;

abstract class Tag extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tags';

    const MODEL_NAME = 'Modules\\Tags\\Model\\Tag';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('body', 'N:varchar(255)');
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