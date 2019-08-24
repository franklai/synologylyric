<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouLyricWikiTest extends LyricsTestCase
{
    protected $module = 'FujirouLyricWiki';

    public function testSearch()
    {
        $artist = 'taylor swift  ';
        $title = 'style';
        $answer = array(
            'artist' => 'Taylor Swift',
            'title'  => 'Style',
            'id'     => 'http://lyrics.wikia.com/Taylor_Swift:Style'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'http://lyrics.wikia.com/Taylor_Swift:Style';
        $path = 'FujirouLyricWiki.taylor_swift.style.txt';

        $this->get($id, $path);
    }
}
