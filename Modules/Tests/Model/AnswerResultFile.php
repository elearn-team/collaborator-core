<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;

class AnswerResultFile extends Base\AnswerResultFile
{
    public static function create()
    {
        $o = new AnswerResultFile();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public static function getByAnswerResultId($id){
        $q = ORM::select('Modules\\Tests\\Model\\AnswerResultFile f', 'f.*')
            ->where('f.answer_result_id = ?', (int)$id);
        return $q->fetchAll();
    }

}
