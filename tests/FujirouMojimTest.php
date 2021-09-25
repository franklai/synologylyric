<?php
declare (strict_types = 1);

require_once 'LyricsTestCase.php';

final class FujirouMojimTest extends LyricsTestCase
{
    protected $module = 'FujirouMojim';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSearch()
    {
        $artist = '坂本真綾';
        $title = '猫背';
        $answer = array(
            'artist' => '',
            'title' => '猫背',
            'id' => 'https://mojim.com/usy105842x25x17.htm',
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://mojim.com/usy105842x25x17.htm';
        $path = 'FujirouMojim.sakamoto_maaya.nekoze.txt';

        $this->get($id, $path);
    }
}
