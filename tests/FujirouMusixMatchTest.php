<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouMusixMatchTest extends LyricsTestCase
{
    protected $module = 'FujirouMusixMatch';

    public function testSearch()
    {
        $artist = 'keane';
        $title = 'Somewhere Only We Know';
        $answer = array(
            'artist' => 'Keane',
            'title' => 'Somewhere Only We Know',
            'id' => '/lyrics/Keane/Somewhere-Only-We-Know',
        );

        try {
            $this->search($artist, $title, $answer);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked\n";
        }
    }

    public function testGet()
    {
        $id = '/lyrics/Natalie-Imbruglia/Torn';
        $path = 'FujirouMusixMatch.natalie_imbruglia.torn.txt';

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
