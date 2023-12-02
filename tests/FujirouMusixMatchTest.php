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
        $artist = 'ヨルシカ';
        $title = '春泥棒';
        $answer = array(
            'artist' => 'Yorushika',
            'title' => 'Spring Thief',
            'id' => '/lyrics/Yorushika/%E6%98%A5%E6%B3%A5%E6%A3%92',
        );

        try {
            $this->search($artist, $title, $answer);
        } catch (BlockedException $e) {
            echo "musixmatch is blocked\n";
        }
    }
}
