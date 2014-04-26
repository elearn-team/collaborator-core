<?php

namespace Modules\Courses\Model;

class File extends Base\File
{
    public static function create()
    {
        $file = new File();
        return $file;
    }

    public function toArray()
    {
        $config = \Bazalt\Config::container();
        $res = [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'title' => $this->title,
            'size' => (double)$this->size,
            'thumbnails' => [
//                'preview' => thumb($this->url, '160x100', ['fit' => true])
            ]
        ];

        return $res;
    }

}