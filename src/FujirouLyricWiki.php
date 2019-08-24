<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

// https://lyrics.fandom.com/api.php
class FujirouLyricWiki
{
    private $apiUrl = 'https://lyrics.fandom.com/api.php';
    private $sitePrefix = 'https://lyrics.fandom.com';

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

    public function search($handle, $artist, $title)
    {
        $count = 0;

        $searchUrl = sprintf(
            "%s?func=getSong&artist=%s&song=%s&fmt=realjson",
            $this->apiUrl, urlencode($artist), urlencode($title)
        );

        $content = FujirouCommon::getContent($searchUrl);

        $obj = json_decode($content, true);

        if ($obj['lyrics'] !== 'Not found') {
            $obj = $this->decodeUTF8($obj);
            $id = $obj['url'];
            $handle->addTrackInfoToList($obj['artist'], $obj['song'], $id, $obj['lyrics']);

            $count = 1;
        }

        return $count;
    }

    public function get($handle, $id)
    {
        $result = array();
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return false;
        }

        $prefix = "<div class='lyricbox'>";
        $suffix = "<div class='lyricsbreak'>";
        $lyricLine = FujirouCommon::getSubString($content, $prefix, $suffix);

        $pattern = "/'>(.*)<div class='lyricsbreak'/";
        $matchedString = FujirouCommon::getFirstMatch($lyricLine, $pattern);
        if (!$matchedString) {
            return false;
        }

        $body = trim(str_replace('<br />', "\n", $matchedString));
        $body = FujirouCommon::decodeHTML($body);
        $body = trim(strip_tags($body));

        $pattern = '/<meta property="og:title" content="(.*)" \/>/';
        $matchedString = FujirouCommon::getFirstMatch($content, $pattern);
        if (!$matchedString) {
            return false;
        }
        $items = explode(':', $matchedString, 2);
        if (!$items || count($items) < 2) {
            return false;
        }
        $artist = $items[0];
        $title = $items[1];

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from LyricWiki',
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
    class TestObj
    {
        private $items;

        public function __construct()
        {
            $this->items = array();
        }

        public function addLyrics($lyric, $id)
        {
            printf("\n");
            printf("song id: %s\n", $id);
            printf("\n");
            printf("== lyric ==\n");
            printf("%s\n", $lyric);
            printf("** END of lyric **\n\n");
        }

        public function addTrackInfoToList($artist, $title, $id, $prefix)
        {
            printf("\n");
            printf("song id: %s\n", $id);
            printf("%s - %s\n", $artist, $title);
            printf("\n");
            printf("== prefix ==\n");
            printf("%s\n", $prefix);
            printf("** END of prefix **\n\n");

            array_push($this->items, array(
                'artist' => $artist,
                'title' => $title,
                'id' => $id,
            ));
        }

        public function getItems()
        {
            return $this->items;
        }
        public function getFirstItem()
        {
            if (count($this->items) > 0) {
                return $this->items[0];
            } else {
                return false;
            }
        }
    }

    $module = 'FujirouLyricWiki';
//     $artist = 'Taylor Swift';
    //     $title = 'back to december';
    //     $artist = 'Eminem';
    //     $title = 'Business';
    //     $artist = 'Taylor Swift';
    //     $title = 'Shake it off';
    $artist = 'Beyoncé';
    $title = 'Hold Up';

    $refClass = new ReflectionClass($module);
    $obj = $refClass->newInstance();

    $testObj = new TestObj();
    $count = $obj->search($testObj, $artist, $title);

    if ($count > 0) {
        $item = $testObj->getFirstItem();

        if (array_key_exists('id', $item)) {
            $obj->get($testObj, $item['id']);
        } else {
            echo "\nno id to query lyric\n";
        }
    } else {
        echo "\nempty result\n";
    }
}
