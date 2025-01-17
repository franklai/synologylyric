<?php
declare(strict_types=1);

require_once 'LyricsTestCase.php';

final class FujirouGeniusTest extends LyricsTestCase
{
    protected $module = 'FujirouGenius';

    protected function setUp(): void
    {
        if (getenv('GITHUB_ACTIONS')) {
            $this->markTestSkipped('GitHub Actions will hit human challenge for genius.');
        }

        parent::setUp();
    }

    public function testSearch()
    {
        $artist = ' keane';
        $title = 'Somewhere Only We Know';
        $answer = array(
            'artist' => 'Keane',
            'title' => 'Somewhere Only We Know',
            'id' => 'https://genius.com/Keane-somewhere-only-we-know-lyrics'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGet()
    {
        $id = 'https://genius.com/Keane-somewhere-only-we-know-lyrics';
        $path = 'FujirouGenius.keane.somewhere_only_we_know.txt';

        $this->get($id, $path);
    }

    public function testSearchJapanese()
    {
        $artist = 'Aimer';
        $title = '夜行列車';
        $answer = array(
            'artist' => 'Aimer',
            'title' => 'Yakou Ressha (夜行列車) ~nothing to lose~',
            'id' => 'https://genius.com/Aimer-yakou-ressha-nothing-to-lose-lyrics'
        );

        $this->search($artist, $title, $answer);
    }

    public function testGetJapanese()
    {
        $id = 'https://genius.com/Maaya-sakamoto-tune-the-rainbow-lyrics';
        $path = 'FujirouGenius.sakamoto_maaya.tune_the_rainbow.txt';

        $this->get($id, $path);
    }

    public function testGetTitleHasAmpersand()
    {
        $id = 'https://genius.com/Taylor-swift-forever-and-always-taylors-version-lyrics';
        $path = 'FujirouGenius.taylor_swift.forever_and_always.txt';

        $this->get($id, $path);
    }

    public function testGetArtistHasAmpersand()
    {
        $id = 'https://genius.com/Sawanohiroyuki-nzk-sh0ut-lyrics';
        $path = 'FujirouGenius.sawano_hiroyuki.sh0ut.txt';

        $this->get($id, $path);
    }
}
