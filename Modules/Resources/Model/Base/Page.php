<?php

namespace Modules\Resources\Model\Base;

abstract class Page extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_resource_pages';

    const MODEL_NAME = 'Modules\\Resources\\Model\\Page';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('user_id', 'N:int(10)');
        $this->hasColumn('category_id', 'UN:int(10)');
        $this->hasColumn('url', 'N:varchar(255)');
        $this->hasColumn('code', 'N:varchar(255)');
        $this->hasColumn('description', 'N:varchar(255)');
        $this->hasColumn('title', 'varchar(255)');
        $this->hasColumn('body', 'mediumtext');
        $this->hasColumn('template', 'N:varchar(255)');
        $this->hasColumn('is_published', 'U:tinyint(1)|0');
        $this->hasColumn('open_in_window', 'U:tinyint(1)');
        $this->hasColumn('type', "ENUM('page','file','url','html')|'page'");
    }

    public function initRelations()
    {
        $this->hasRelation('Category', new \Bazalt\ORM\Relation\One2One('Modules\\Resources\\Model\\Category', 'category_id', 'id'));
        $this->hasRelation('Videos', new \Bazalt\ORM\Relation\One2Many('Modules\\Resources\\Model\\Video', 'id', 'page_id'));
        $this->hasRelation('Files', new \Bazalt\ORM\Relation\One2Many('Modules\\Resources\\Model\\File', 'id', 'page_id'));
        $this->hasRelation('User', new \Bazalt\ORM\Relation\One2One('Bazalt\\Auth\\Model\\User', 'user_id', 'id'));
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