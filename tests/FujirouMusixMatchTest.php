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

        try {
            $this->search($artist, $title, $answer);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked\n";
        }
    }

    public function testGet()
    {
        $id = '/lyrics/RADWIMPS/September-San';
        $path = 'FujirouMusixMatch.radwimps.september_san.txt';

        try {
            $this->get($id, $path);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked\n";
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

        try {
            $this->search($artist, $title, $answer);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked\n";
        }
    }
}
