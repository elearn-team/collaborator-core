<?php
namespace Modules\Tests\Model;

use Bazalt\ORM;

class File extends Base\File
{
    public static function create()
    {
        $o = new File();
        $o->site_id = \Bazalt\Site::getId();
        return $o;
    }

    public function toArray()
    {
//        $config = \Bazalt\Config::container();
        $res = parent::toArray();

        $res['url'] = $res['file'];
//        $res['thumbnail'] = $config['thumb.prefix'].
//            thumb(SITE_DIR .'/..'. $this->file, '80x80');
        return $res;
    }
}
