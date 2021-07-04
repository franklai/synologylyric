<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouMetroLyricsTest extends LyricsTestCase
{
    protected $module = 'FujirouMetroLyrics';

    protected function setUp(): void
    {
        $this->markTestSkipped('metrolyrics site is down.');

        parent::setUp();
    }

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

    public function testGet2()
    {
        $id = 'https://www.metrolyrics.com/faded-lyrics-alan-walker.html';
        $path = 'FujirouMetroLyrics.alan_walker.faded.txt';

        $this->get($id, $path);
    }
}
