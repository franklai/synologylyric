<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

define('DEBUG', true);

final class FujirouMusixMatchTest extends LyricsTestCase
{
    protected $module = 'FujirouMusixMatch';

    public function testSearch()
    {
        $artist = 'taylor swift  ';
        $title = 'style';
        $answer = array(
            'artist' => 'Taylor Swift',
            'title'  => 'Style',
            'id'     => '/lyrics/Taylor-Swift/Style-2'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = '/lyrics/Taylor-Swift/Style-2';
        $path = 'FujirouMusixMatch.taylor_swift.style.txt';

        $this->get($id, $path);
    }

    public function testSearchJapanese()
    {
        $artist = 'Galileo Galilei';
        $title = '鳥と鳥';
        $answer = array(
            'artist' => 'Galileo Galilei',
            'title'  => '鳥と鳥',
            'id'     => '/lyrics/Galileo-Galilei/%E9%B3%A5%E3%81%A8%E9%B3%A5'
        );

        $this->search($artist, $title, $answer);
    }
}
