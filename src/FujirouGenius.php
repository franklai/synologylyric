<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouGenius
{
    private $apiUrl = 'https://genius.com/api/search/multi';

    public function __construct()
    {
    }

    public function getLyricsList($artist, $title, $info)
    {
        return $this->search($info, $artist, $title);
    }
    public function getLyrics($id, $info)
    {
        return $this->get($info, $id);
    }

    private function adjustTitleForQuery($title)
    {
        $title = preg_replace('/[\[(]radio edit[\])]/i', '', $title);
        $title = preg_replace('/[\[(]album version[\])]/i', '', $title);
        return $title;
    }

    private function getQuery($artist, $title)
    {
        $title = $this->adjustTitleForQuery($title);

        $special_chars = ["(", ")",];
        $stripped_artist = str_replace($special_chars, "", $artist);
        $stripped_title = str_replace($special_chars, "", $title);
        return urlencode("$stripped_artist $stripped_title");
    }

    public function search($handle, $artist, $title)
    {
        $count = 0;

        $url = $this->apiUrl;
        $query = $this->getQuery($artist, $title);
        FujirouCommon::printMsg("query string is [$query]");
        $search_url = "$url?q=$query";

        $content = FujirouCommon::getContent($search_url);
        $obj = json_decode($content, true);
        $sections = $obj['response']['sections'];

        if (!$sections) {
            FujirouCommon::printMsg("Failed to get sections of search");
            FujirouCommon::printMsg($content);
            return 0;
        }

        $song_section = null;
        $top_hit_section = null;
        foreach ($sections as $section) {
            if ($section['type'] === 'top_hit') {
                $top_hit_section = $section;
            }
            if ($section['type'] === 'song') {
                $song_section = $section;
                break;
            }
        }

        if (!$song_section) {
            return $count;
        }
        $results = [];

        foreach ($song_section['hits'] as $hit) {
            array_push($results, $hit['result']);
        }

        if ($top_hit_section && count($results) === 0) {
            if (count($top_hit_section['hits']) > 0) {
                array_push($results, $top_hit_section['hits'][0]['result']);
            }
        }

        foreach ($results as $result) {
            $artist = $result['primary_artist']['name'];
            $title = $result['title'];
            $id = $result['url'];

            $handle->addTrackInfoToList($artist, $title, $id, '');
            $count += 1;
        }

        return $count;
    }

    private function get_by_lyrics_root($content)
    {
        $prefix = '<div data-lyrics-container="true"';
        $suffix = '<div class="SectionLeaderboard';

        $body = FujirouCommon::getSubString($content, $prefix, $suffix);
        if ($body) {
            // add newline for ad block
            $body = str_replace(
                '<div class="SidebarAd__Container',
                '<br/><div class="',
                $body

            );

            // add newline for lyrics-container
            $body = str_replace(
                '<div data-lyrics-container="true"',
                '<br/><div data-lyrics-container="true"',
                $body
            );
        }
        return $body;
    }

    private function remove_non_lyric_tags($body)
    {
        // remove sharing button
        $body = preg_replace('/<button.*?<\/button>/', '', $body);
        $body = preg_replace('/<label.*?<\/label>/', '', $body);
        $body = preg_replace('/<div class="EmbedForm__Copy.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="LyricsEditExplainer.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="RecommendedSongs.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="ShareButtons.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="StubhubLink.*?<\/div>/', '', $body);
        $body = preg_replace('/<h2.*?<\/h2>/', '', $body);

        return $body;
    }

    public function get($handle, $id)
    {
        $result = array();
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return false;
        }

        $body = $this->get_by_lyrics_root($content);
        if (!$body) {
            FujirouCommon::printMsg("Failed to get content from Lyrics__Root");
            FujirouCommon::printMsg($content);
            return false;
        }

        $body = FujirouCommon::decodeHTML($body);
        $body = str_replace('<br/>', "\n", $body);

        $body = $this->remove_non_lyric_tags($body);

        $body = trim(strip_tags($body));

        $pattern = '<meta content="(.*?) – (.*?)" property="twitter:title" />';
        $artist = trim(FujirouCommon::getFirstMatch($content, $pattern));
        $artist = str_replace("\xc2\xa0", " ", $artist);
        $artist = FujirouCommon::decodeHTML($artist);
        $pattern = '<meta content=".*? – (.*?)" property="twitter:title" />';
        $title = trim(FujirouCommon::getFirstMatch($content, $pattern));
        $title = preg_replace('/[\x{200B}-\x{200D}]/u', '', $title);
        $title = FujirouCommon::decodeHTML($title);

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from Genius Lyrics',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function decodeUTF8($obj)
    {
        $tryDecodeFunc = function ($str) {
            if ($str) {
                $length = strlen($str);
                $temp = '';

                // if decode failed, remove character from end and decode again
                while (empty($temp) && $length > 0) {
                    $temp = utf8_decode(substr($str, 0, $length));

                    $jsonStr = json_encode($temp);
                    if (empty($jsonStr) || 'null' === $jsonStr) {
                        // will lead to json_encode() fail
                        $temp = '';
                    }
                    $length -= 1;
                }

                $str = $temp;
            }

            return $str;
        };

        return array_map($tryDecodeFunc, $obj);
    }
}

if (!debug_backtrace()) {
    $module = "FujirouGenius";
    define("debug", true);

    $refClass = new ReflectionClass($module);
    $instance = $refClass->newInstance();
    $test_object = new TestObject();

    if ($argc === 2) {
        $url = $argv[1];

        echo "get [$url]\n";
        $instance->get($test_object, $url);

        $lyric = $test_object->getLyric();

        echo "=== lyric ===\n";
        echo $lyric;
        return;
    }

    $artist = 'Mariah Carey';
    $title = 'one sweet day';
    if ($argc === 3) {
        $artist = $argv[1];
        $title = $argv[2];
    }

    echo "search for [$artist] [$title]\n";
    $instance->search($test_object, $artist, $title);

    $item = $test_object->getFirstItem();

    var_dump($item);

    $url = $item['id'];

    echo "get [$url]\n";
    $instance->get($test_object, $url);

    $lyric = $test_object->getLyric();

    echo "=== lyric ===\n";
    echo $lyric;
}
