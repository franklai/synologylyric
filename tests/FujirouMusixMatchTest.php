<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouMusixMatchTest extends LyricsTestCase
{
    protected $module = 'FujirouMusixMatch';

    public function testSearch()
    {
        $artist = 'live forever';
        $title = 'oasis';
        $answer = array(
            'artist' => 'Oasis',
            'title' => 'Live Forever',
            'id' => '/lyrics/Oasis/Live-Forever',
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = '/lyrics/Oasis/Live-Forever';
        $path = 'FujirouMusixMatch.oasis.live_forever.txt';

        try {
            $this->get($id, $path);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked";
        }
    }

    public function testSearchJapanese()
    {
        $artist = 'Galileo Galilei';
        $title = '鳥と鳥';
        $answer = array(
            'artist' => 'Galileo Galilei',
            'title' => '鳥と鳥',
            'id' => '/lyrics/Galileo-Galilei/%E9%B3%A5%E3%81%A8%E9%B3%A5',
        );

        $this->search($artist, $title, $answer);
    }
}
