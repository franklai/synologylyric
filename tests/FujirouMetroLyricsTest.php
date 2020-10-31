<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouMetroLyricsTest extends LyricsTestCase
{
    protected $module = 'FujirouMetroLyrics';

    public function testSearch()
    {
        $artist = 'taylor swift  ';
        $title = 'style';
        $answer = array(
            'artist' => 'Taylor Swift',
            'title'  => 'Style',
            'id'     => 'https://www.metrolyrics.com/style-lyrics-taylor-swift.html'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://www.metrolyrics.com/style-lyrics-taylor-swift.html';
        $path = 'FujirouMetroLyrics.taylor_swift.style.txt';

        $this->get($id, $path);
    }
}
