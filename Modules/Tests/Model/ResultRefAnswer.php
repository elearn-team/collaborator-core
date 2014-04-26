<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;

class ResultRefAnswer extends Base\ResultRefAnswer
{
    public static function getAnswersByResultId($resultId){
        $q = ORM::select('Modules\\Tests\\Model\\ResultRefAnswer r', 'r.*')
            ->where('r.result_id = ?', $resultId);
        return $q->fetchAll();
    }

}
