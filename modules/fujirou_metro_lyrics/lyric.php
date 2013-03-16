<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouMetroLyrics {
    private $_site = 'http://www.metrolyrics.com';
    private $_apiKey = '196f657a46afb63ce3fd2015b9ed781280337ea7';

    public function __construct() {
    }

    public function getLyricsList($artist, $title, $info) {
        return $this->search($info, $artist, $title);
    }
    public function getLyrics($id, $info) {
        return $this->get($info, $id);
    }

    public function search($handle, $artist, $title) {
        $count = 0;

        $keyword = sprintf("%s %s", $artist, $title);

        // http://www.metrolyrics.com/api/v1/multisearch/all/X-API-KEY/196f657a46afb63ce3fd2015b9ed781280337ea7?find=taylor+swift+love+stor
        $searchUrl = sprintf(
            "%s/api/v1/multisearch/all/X-API-KEY/%s?find=%s",
            $this->_site, $this->_apiKey, urlencode($keyword)
        );

        $content = FujirouCommon::getContent($searchUrl);
        if (!$content) {
            return $count;
        }

        $list = $this->parseSearchResult($content);

        for ($idx = 0; $idx < count($list); $idx++) {
            $obj = $list[$idx];

            $handle->addTrackInfoToList(
                $obj['artist'],
                $obj['title'],
                $obj['id'],
                $obj['partial']
            );
        }

        return count($list);
    }

    public function get($handle, $id) {
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return FALSE;
        }

        $prefix = '<div id="lyrics-body">';
        $suffix = '</div>';

        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);

        $lyric = str_replace('<br />', "\n", $lyric);
        $lyric = trim(strip_tags($lyric));
        $lyric = FujirouCommon::decodeHTML($lyric);

        // remove extra ad line
        $pattern = '/\[ From: http:\/\/www.metrolyrics.com\/.*.html \]/';
        $lyric = preg_replace($pattern, '', $lyric);

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $json = json_decode($content, TRUE);

        if (!array_key_exists('results', $json)) {
            return $result;
        }

        $lyricsItems = null;
        foreach ($json['results'] as $tmp) {
            if (array_key_exists('t', $tmp) && $tmp['t'] === 'Lyrics') {
                $lyricsItems = $tmp;
                break;
            }
        }

        if (!$lyricsItems || !array_key_exists('d', $lyricsItems)
            || count($lyricsItems['d']) === 0) {
            return $result;
        }

        $firstItem = $lyricsItems['d'][0];

        $artistTitle = explode('<br />', $firstItem['p']);
        if (count($artistTitle) !== 2) {
            return $result;
        }

        $item = array(
            'artist' => strip_tags($artistTitle[0]),
            'title'  => strip_tags($artistTitle[1]),
            'id'     => $this->_site . '/' . $firstItem['u'],
            'partial'=> ''
        );

        array_push($result, $item);

        return $result;
    }
}

if (!debug_backtrace()) {
    class TestObj {
        private $items;

        function __construct() {
            $this->items = array();
        }

        public function addLyrics($lyric, $id) {
            printf("\n");
            printf("song id: %s\n", $id);
            printf("\n");
            printf("== lyric ==\n");
            printf("%s\n", $lyric);
            printf("** END of lyric **\n\n");
        }

        public function addTrackInfoToList($artist, $title, $id, $prefix) {
            printf("\n");
            printf("song id: %s\n", $id);
            printf("%s - %s\n", $artist, $title);
            printf("\n");
            printf("== prefix ==\n");
            printf("%s\n", $prefix);
            printf("** END of prefix **\n\n");

            array_push($this->items, array(
                'artist' => $artist,
                'title'  => $title,
                'id'     => $id
            ));
        }

        function getItems() {
            return $this->items;
        }
        function getFirstItem() {
            if (count($this->items) > 0) {
                return $this->items[0];
            } else {
                return FALSE;
            }
        }
    }

    $module = 'FujirouMetroLyrics';
    $artist = 'Taylor Swift';
//     $title = 'back to december';
    $title = 'red';
//     $title = 'red (original demo version)';
//     $title = 'album red';
//     $badTitle = 'tailer sfiwt';


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
    }
}
// vim: expandtab ts=4
?>

