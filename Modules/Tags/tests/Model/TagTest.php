<?php

namespace Modules\Tags\Tests\Model;


use Modules\Tags\Model\Tag;
use Modules\Tags\Model\TagRefElement;

class TagTest extends \PHPUnit_Framework_TestCase
{
    protected $models = [];

    public function setUp() {

        parent::setUp() ;
    }

    public function tearDown()
    {
        foreach($this->models as $o) {
            $o->delete();
        }

        parent::tearDown();
    }

    public function testGet()
    {
        $tag = Tag::create();
        $tag->body = 'test_tag';
        $tag->save();
        $this->models []= $tag;

        $foundTag = Tag::get('test_tag');
        $this->assertEquals($foundTag->body, $tag->body);
    }

    public function testAddTag()
    {
        $elementId = 10;
        $type = 'course';
        Tag::addTag($elementId, $type, 'test_tag2');

        $foundTag = Tag::get('test_tag2');
        $this->assertEquals($foundTag->body, 'test_tag2');

        $this->assertTrue(TagRefElement::hasTag($elementId, $type, $foundTag->id));
    }

    public function testClearTags()
    {
        $elementId = 10;
        $type = 'course';

        Tag::clearTags($elementId, $type);
        $foundTag = Tag::get('test_tag2');
        $this->models []= $foundTag;

        $this->assertFalse(TagRefElement::hasTag($elementId, $type, $foundTag->id));
    }
}