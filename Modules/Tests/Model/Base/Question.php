<?php
/**
 * Question.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_tests_questions"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $site_id
 * @property-read int $test_id
 * @property-read int $type
 * @property-read string $body
 * @property-read int $is_deleted
 * @property-read datetime $created_at
 * @property-read datetime $updated_at
 * @property-read int $created_by
 * @property-read int $updated_by
 */
namespace Modules\Tests\Model\Base;

abstract class Question extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tests_questions';

    const MODEL_NAME = 'Modules\Tests\Model\Question';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('test_id', 'U:int(10)');
        $this->hasColumn('type', 'varchar(255)');
        $this->hasColumn('body', 'N:text');
        $this->hasColumn('weight', 'U:int(10)|1');
        $this->hasColumn('is_deleted', 'U:tinyint(1)|0');
        $this->hasColumn('allow_add_files', 'U:tinyint(1)|0');
        $this->hasColumn('created_at', 'N:datetime');
        $this->hasColumn('updated_at', 'N:datetime');
        $this->hasColumn('created_by', 'UN:int(10)');
        $this->hasColumn('updated_by', 'UN:int(10)');
    }

    public function initRelations()
    {
        $this->hasRelation('Answers', new \Bazalt\ORM\Relation\One2Many('Modules\\Tests\\Model\\Answer', 'id', 'question_id'));
        $this->hasRelation('Files', new \Bazalt\ORM\Relation\One2Many('Modules\\Tests\\Model\\File', 'id', 'question_id'));
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