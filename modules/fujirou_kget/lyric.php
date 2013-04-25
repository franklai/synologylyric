<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        require(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        require(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouKget {
    private $searchPrefix = 'http://www.kget.jp/search/index.php';
    private $lyricPrefix = 'http://www.kget.jp';

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

        // search
        // http://www.kget.jp/search/index.php?c=0&r=%E5%9D%82%E6%9C%AC%E7%9C%9F%E7%B6%BE&t=tune+the+rainbow&v=&f=
        $searchUrl = sprintf(
            "%s?c=0&r=%s&t=%s",
            $this->searchPrefix, urlencode($artist), urlencode($title)
        );

        $content = FujirouCommon::getContent($searchUrl);

        $item = $this->parseSearchResult($content);
        if ($item === FALSE) {
            return $count;
        }

        $handle->addTrackInfoToList(
            $item['artist'],
            $item['title'],
            $item['id'],
            $item['prefix']
        );
        $count = 1;

        return $count;
    }

    private function parseSearchResult($content) {
        $prefix = '<ul class="songlist">';
        $suffix = '</ul>';

        if (strpos($content, $prefix) === FALSE) {
            return FALSE;
        }
        $resultList = FujirouCommon::getSubString($content, $prefix, $suffix);

        $pattern = '/<a class="lyric-anchor" href="([^"]+)">/';
        $lyricUrl = sprintf(
            "%s%s",
            $this->lyricPrefix,
            FujirouCommon::getFirstMatch($resultList, $pattern)
        );

        $pattern = '/<h2 class="title">([^<]+)<\/h2>/';
        $title = FujirouCommon::getFirstMatch($resultList, $pattern);

        $pattern = '/<p class="artist"><a href="[^"]+">([^<]+)<\/a><\/p>/';
        $artist  = FujirouCommon::getFirstMatch($resultList, $pattern);

        $prefix = '<div class="begin"><span>';
        $suffix = '</div>';
        $pattern = '/<strong>([^<]+)<\/strong>/';
        $prefix = FujirouCommon::getFirstMatch(
            FujirouCommon::getSubString($resultList, $prefix, $suffix),
            $pattern
        );

        return array(
            'artist' => $artist,
            'title' => $title,
            'id' => $lyricUrl,
            'prefix' => $prefix
        );
    }

    public function get($handle, $id) {
        $result = array();
        $lyric = '';

        // id should be url of lyric
        if (strchr($id, $this->lyricPrefix) === FALSE) {
            return FALSE;
        }

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return FALSE;
        }

        $prefix = '<div id="lyric-trunk">';
        $suffix = '</div>';
        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);

        $lyric = strip_tags($lyric);
        $lyric = FujirouCommon::decodeHTML($lyric);

        $handle->addLyrics($lyric, $id);

        return TRUE;
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

    $module = 'FujirouKget';
    $artist = '坂本真綾';
    $title = 'tune the rainbow';

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


// vim: expandtab ts=4
?>

