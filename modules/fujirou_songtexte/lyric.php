<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouSongTexte {
    private $_site = 'http://www.songtexte.com';

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

        $searchUrl = sprintf(
            "%s/search?q=%s&c=songs",
            $this->_site, rawurlencode("$artist - $title")
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

        $prefix = '<div id="lyrics">';
        $suffix = '</div>';

        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);

        $lyric = str_replace('<br/>', "\n", $lyric);
        $lyric = trim(strip_tags($lyric));
        $lyric = FujirouCommon::decodeHTML($lyric);

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $prefix = '<div class="songResultTable">';
        $suffix = '</tbody>';

        $oneLineContent = FujirouCommon::toOneLine($content);
        $searchResult = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        if (!$searchResult) {
            return $result;
        }

        $items = explode('</tr>', $searchResult);
        foreach ($items as $item) {
            $pattern = '/<td class="song"><a href="[^"]+" title=".*"><span>(.*)<\/span><\/a>/U';
            $title = FujirouCommon::getFirstMatch($item, $pattern);

            $pattern = '/<td class="song"><a href="([^"]+)" title=".*"><span>.*<\/span><\/a>/U';
            $url = FujirouCommon::getFirstMatch($item, $pattern);

            $pattern = '/<td class="artist"><a href="[^"]+" title=".*"><span>(.*)<\/span><\/a>/U';
            $artist = FujirouCommon::getFirstMatch($item, $pattern);

            if (!$title || !$artist || !$url) {
                continue;
            }

            $item = array(
                'artist' => strip_tags($artist),
                'title'  => strip_tags($title),
                'id'     => sprintf("%s/%s", $this->_site, $url),
                'partial'=> ''
            );

            array_push($result, $item);
        }

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

    $module = 'FujirouSongTexte';
//     $artist = 'pitbull';
//     $title = 'We Are One (Ole Ola)';

//     $title = 'back to december';
//     $title = 'red (original demo version)';
//     $title = 'album red';
//     $badTitle = 'tailer sfiwt';
    $artist = 'ONE OK ROCK';
    $title = 'heartache';


    $refClass = new ReflectionClass($module);
    $obj = $refClass->newInstance();
    
    $testObj = new TestObj();
    $count = $obj->search($testObj, $artist, $title);

    if ($count > 0) {
        $item = $testObj->getFirstItem();

        if (array_key_exists('id', $item)) {
            echo "\nid in first item is [" .$item['id']. "]\n";
            $obj->get($testObj, $item['id']);
        } else {
            echo "\nno id to query lyric\n";
        }
    }
}
// vim: expandtab ts=4
?>

