<?php

namespace Modules\Resources\Model;

class Video extends Base\Video
{
    public static function create()
    {
        $video = new Video();
        return $video;
    }
}