<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        require(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        require(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouOiktv {
    private $site = 'http://www.oiktv.com';
    private $lyricsPrefix = 'http://lyrics.oiktv.com';

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

        // http://lyrics.oiktv.com/search.php?sn=%E7%9B%A7%E5%BB%A3%E4%BB%B2&an=&ln=%E7%84%A1%E6%95%B5%E9%90%B5%E9%87%91%E5%89%9B&lrc=&sx=all
        // http://www.oiktv.com/search.php?sn=taylor+swift+&an=&ln=love+story&lrc=&sx=all&type=1
        // http://www.oiktv.com/search.php?sn=Taylor+Swift&an=&ln=back+to+december&lrc=&sx=m%2Cw%2Cg%2Cj%2Ce%2Ch&type=1
        $searchUrl = sprintf(
            "%s/search.php?sn=%s&ln=%s&sx=all&type=1&an=&lrc=",
            $this->site, urlencode($artist), urlencode($title)
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

        $prefix = '<dd><p>';
        $suffix = '</p></dd>';

        $oneLineContent = FujirouCommon::toOneLine($content);

        $lyric = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        $lyric = str_replace('</span><p>', "\n\n", $lyric);
        $lyric = str_replace($prefix, '', $lyric);
        $lyric = str_replace($suffix, '', $lyric);
        $lyric = str_replace('<br />', "\n", $lyric);

        $lyric = trim(strip_tags($lyric));

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        if (FALSE === strpos($content, '/lyric-')) {
            return $result;
        }

        $pattern = '/歌名：<a href="[^"]+".*>(.*)<\/a>/';
        $titleList = FujirouCommon::getAllFirstMatch($content, $pattern);

        $pattern = '/歌手：<a href="[^"]+".*>(.*)<\/a>/';
        $artistList = FujirouCommon::getAllFirstMatch($content, $pattern);

        $pattern = '/歌名：<a href="([^"]+)".*>.*<\/a>/';
        $urlList = FujirouCommon::getAllFirstMatch($content, $pattern);

        $count = count($artistList);
        if ($count != count($titleList) ||
            $count != count($urlList)) {
            return $result;
        }

        for ($idx = 0; $idx < $count; ++$idx) {
            $item = array(
                'artist' => $artistList[$idx],
                'title'  => $titleList[$idx],
                'id'     => $urlList[$idx],
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

    $module = 'FujirouOiktv';
    $artist = 'Taylor Swift';
    $title = 'back to december';
//     $title = 'red (original demo version)';
//     $title = 'album red';


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

