<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouKkboxTest extends LyricsTestCase
{
    protected $module = 'FujirouKkbox';

    public function testSearch()
    {
        $artist = '茄子蛋';
        $title = '浪流連';
        $answer = array(
            'artist' => '茄子蛋',
            'title' => '浪流連',
            'id' => 'https://www.kkbox.com/tw/tc/song/22y00C6.d.JIIzpXIIzpX0XL-index.html',
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://www.kkbox.com/tw/tc/song/22y00C6.d.JIIzpXIIzpX0XL-index.html';
        $path = 'FujirouKkbox.egg_plantegg.txt';

        $this->get($id, $path);
    }

    public function testNoLyrics()
    {
        $id = 'https://www.kkbox.com/tw/tc/song/vZP00L48426WRyKqWRyKq0XL-index.html';
        $path = $path = 'FujirouKkbox.no_lyrics.txt';

        $this->get($id, $path);
    }
}
