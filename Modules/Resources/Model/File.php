<?php

namespace Modules\Resources\Model;

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
//            'thumbnailUrl' => thumb($this->url, '80x80'),
            'size' => (double)$this->size,
            'extension' => $this->extension,
            'index_page' => $this->index_page,
            'width' => (int)$this->width,
            'height' => (int)$this->height,
            'thumbnails' => [
//                'preview' => thumb($this->url, '160x100', ['fit' => true])
            ]
        ];

        return $res;
    }

    public static function removeDirectory($dir)
    {
        if (file_exists($dir)) {
            if ($objs = glob($dir . "/*")) {
                foreach ($objs as $obj) {
                    is_dir($obj) ? File::removeDirectory($obj) : unlink($obj);
                }
            }
            rmdir($dir);
        }
    }

    public static function parseUrl($url)
    {
        $h = explode('//', $url);
        if (count($h) == 1) {
            $url = 'http://' . $h[0];
        }

        $parse = explode('.', $url);

        if ($parse[1] == 'youtube') {
            return File::youtubeUrl($url);
        } elseif ($parse[0] == 'https://vimeo' || $parse[0] == 'http://vimeo') {
            return File::vimeoUrl($url);
        } else {
            return $url;
        }

    }

    public static function youtubeUrl($url)
    {
        $code = explode('v=', $url);
        return 'http://www.youtube.com/embed/' . $code[count($code) - 1];
    }

    public static function vimeoUrl($url)
    {
        $vimCode = explode('/', $url);
        return '//player.vimeo.com/video/' . $vimCode[3] . '?badge=0';
    }

}