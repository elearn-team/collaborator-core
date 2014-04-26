<?php

namespace Modules\Resources\Model\Base;

abstract class TagRefPage extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_resource_pages_ref_tags';

    const MODEL_NAME = 'Modules\\Resources\\Model\\TagRefPage';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('tag_id', 'P:int(10)');
        $this->hasColumn('page_id', 'P:int(10)');
    }

    public function initRelations()
    {
    }
}