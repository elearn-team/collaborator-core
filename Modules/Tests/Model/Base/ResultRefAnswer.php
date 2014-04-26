<?php
/**
 * ResultRefAnswer.php
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   SVN: $Id$
 */
/**
 * Data model for table "els_tests_results_ref_answers"
 *
 * @category  DataModels
 * @package   DataModel
 * @author    Bazalt CMS (http://bazalt-cms.com/)
 * @version   Release: $Revision$
 *
 * @property-read int $id
 * @property-read int $result_id
 * @property-read int $question_id
 * @property-read int $answer_id
 * @property-read int $is_right
 */
namespace Modules\Tests\Model\Base;

abstract class ResultRefAnswer extends \Bazalt\ORM\Record
{
    const TABLE_NAME = 'els_tests_results_ref_answers';

    const MODEL_NAME = 'Modules\\Tests\\Model\\ResultRefAnswer';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, self::MODEL_NAME);
    }

    protected function initFields()
    {
        $this->hasColumn('id', 'PUA:int(10)');
        $this->hasColumn('result_id', 'U:int(10)');
        $this->hasColumn('question_id', 'U:int(10)');
        $this->hasColumn('answer_id', 'U:int(10)');
        $this->hasColumn('is_right', 'U:tinyint(1)|0');
        $this->hasColumn('text_answer', 'N:text');
        $this->hasColumn('mark', 'U:int(10)');
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