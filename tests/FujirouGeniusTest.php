<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouGeniusTest extends LyricsTestCase
{
    protected $module = 'FujirouGenius';

    public function testSearch()
    {
        $artist = 'taylor swift  ';
        $title = 'style';
        $answer = array(
            'artist' => 'Taylor Swift',
            'title'  => 'Style',
            'id'     => 'https://genius.com/Taylor-swift-style-lyrics'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://genius.com/Taylor-swift-style-lyrics';
        $path = 'FujirouGenius.taylor_swift.style.txt';

        $this->get($id, $path);
    }

    public function testSearchJapanese()
    {
        $artist = 'RADWIMPS';
        $title = 'スパークル (Movie Ver.)';
        $answer = array(
            'artist' => 'RADWIMPS',
            'title'  => 'スパークル (Sparkle) [Movie Version]',
            'id'     => 'https://genius.com/Radwimps-sparkle-movie-version-lyrics'
        );

        $this->search($artist, $title, $answer);
    }
}
