<?php
/**
 * File.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_tests_files"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $site_id
 * @property-read int $question_id
 * @property-read int $type
 * @property-read string $name
 * @property-read string $file
 * @property-read datetime $created_at
 * @property-read datetime $updated_at
 * @property-read int $created_by
 * @property-read int $updated_by
 */
namespace Modules\Tests\Model\Base;

abstract class AnswerResultFile extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tests_answer_result_files';

    const MODEL_NAME = 'Modules\Tests\Model\AnswerResultFile';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('site_id', 'U:int(10)');
        $this->hasColumn('answer_result_id', 'U:int(10)');
        $this->hasColumn('name', 'N:varchar(255)');
        $this->hasColumn('file', 'N:varchar(255)');
        $this->hasColumn('extension', 'N:varchar(5)');
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