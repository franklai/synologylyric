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
        // http://www.oiktv.com/search/lyrics/taylor%20swift%20back%20to%20december
        $searchUrl = sprintf(
            "%s/search/lyrics/%s",
            $this->site, urlencode("$artist $title")
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

//         $prefix = '<p class="col-sm-12"';
        $prefix = '</span><br />';
        $suffix = '</p>';

//         $oneLineContent = FujirouCommon::toOneLine($content);

        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);

        $lyric = str_replace($prefix, '', $lyric);
        $lyric = str_replace($suffix, '', $lyric);
        $lyric = str_replace('<br />', '', $lyric);

        // remove ad line
        $prefix = '~查詢更多歌詞';
        $suffix = '</a>~';
        $more_lyric_ad = FujirouCommon::getSubString($lyric, $prefix, $suffix);

        $lyric = str_replace($more_lyric_ad, '', $lyric);

        $lyric = trim(strip_tags($lyric));

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        if (FALSE === strpos($content, '<article class="thumb cat-sports">')) {
            return $result;
        }

        $oneLineContent = FujirouCommon::toOneLine($content);

        $pattern = '/<article class="thumb cat-sports">.*?<a href="([^"]+)".*?>([^<]+).*?\[([^\]]+)\].*?<\/article>/';

        $matches = FujirouCommon::getAllMatches($oneLineContent, $pattern);

        $listUrl = $matches[1];
        $listTitle = $matches[2];
        $listArtist = $matches[3];

        $count = count($listUrl);
        if ($count != count($listTitle) || $count != count($listArtist)) {
            return $result;
        }

        for ($idx = 0; $idx < $count; ++$idx) {
            $item = array(
                'artist' => $listArtist[$idx],
                'title'  => $listTitle[$idx],
                'id'     => $listUrl[$idx],
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

//     $id = 'http://www.oiktv.com/lyrics/lyric-1084830.html'; // B'z

    if ($argc < 2) {
        $count = $obj->search($testObj, $artist, $title);

        if ($count > 0) {
            $item = $testObj->getFirstItem();
            if (array_key_exists('id', $item)) {
                $id = $item['id'];
            } else {
                echo "\nno id to query lyric\n";
                exit;
            }
        }
    } else {
        $id = $argv[1];
    }

    $obj->get($testObj, $id);
}
// vim: expandtab ts=4
?>

