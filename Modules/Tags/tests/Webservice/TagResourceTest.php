<?php

namespace Modules\Tags\Tests\Webservice;

use Modules\Tags\Webservice\TagsResource;
use Modules\Tags\Model\Tag;


class TagResourceTest extends \Bazalt\Auth\Test\BaseCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initApp(getWebServices());
    }

    public function testFind()
    {
        $tags = [];

        $tag = Tag::create();
        $tag->body = 'ttg';
        $tag->save();
        $this->models []= $tag;
        $tags ['tags'][]= [
            'id' => 'ttg',
            'text' => 'ttg'
        ];

        $tag = Tag::create();
        $tag->body = 'ttg2';
        $tag->save();
        $this->models []= $tag;
        $tags ['tags'][]= [
            'id' => 'ttg2',
            'text' => 'ttg2'
        ];

        $_GET['q'] = 'ttg';
        $response = new \Bazalt\Rest\Response(200, $tags);
        $this->assertResponse('GET /tags', [], $response);
    }
}