<?php
declare (strict_types = 1);

use PHPUnit\Framework\TestCase;

class LyricsTestCase extends TestCase
{
    private static $test_keys = array('artist', 'title', 'id');
    protected $module;
    protected $instance;
    protected $test_object;

    protected function setUp(): void
    {
        $refClass = new ReflectionClass($this->module);
        $this->instance = $refClass->newInstance();
        $this->test_object = new TestObject();
    }

    protected function search($artist, $title, $answer)
    {
        $count = $this->instance->search($this->test_object, $artist, $title);
        $item = $this->test_object->getFirstItem();

        foreach (self::$test_keys as $key) {
            $this->assertEquals($answer[$key], $item[$key]);
        }
    }

    protected function get($id, $filename)
    {
        $this->instance->get($this->test_object, $id);
        $lyric = $this->test_object->getLyric();

        $this->assertEquals(file_get_contents(__DIR__."/content/$filename"), $lyric);
    }
}
