<?php
declare (strict_types = 1);

use PHPUnit\Framework\TestCase;

final class FujirouMetroLyricsTest extends TestCase
{
    public function testSearch()
    {
        $module = 'FujirouMetroLyrics';
        $artist = 'Taylor Swift';
        $title = 'Style';

        $refClass = new ReflectionClass($module);
        $obj = $refClass->newInstance();
        $testObj = new TestObject();

        $count = $obj->search($testObj, $artist, $title);

        $item = $testObj->getFirstItem();

        $this->assertEquals($item['id'], 'http://www.metrolyrics.com/style-lyrics-taylor-swift.html');
        $this->assertEquals($item['title'], $title);
        $this->assertEquals($item['artist'], $artist);
    }

    public function testGet()
    {
        $module = 'FujirouMetroLyrics';
        $id = 'http://www.metrolyrics.com/style-lyrics-taylor-swift.html';

        $refClass = new ReflectionClass($module);
        $obj = $refClass->newInstance();
        $testObj = new TestObject();

        $obj->get($testObj, $id);
        $lyric = $testObj->getLyric();

        $this->assertEquals(strlen($lyric), 1832);
    }
}
