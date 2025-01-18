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
            'artist' => '茄子蛋 (EggPlantEgg)',
            'title' => '浪流連',
            'id' => 'https://www.kkbox.com/tw/tc/song/5-LQH1Tei4L5oBvtCR',
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://www.kkbox.com/tw/tc/song/4ohAwOvqtWt5ngJ4No';
        $path = 'FujirouKkbox.egg_plantegg.txt';

        $this->get($id, $path);
    }

    public function testNoLyrics()
    {
        $id = 'https://www.kkbox.com/tw/tc/song/CtWp-4QFUSEGYYd43P';
        $path = $path = 'FujirouKkbox.no_lyrics.txt';

        $this->get($id, $path);
    }
}
