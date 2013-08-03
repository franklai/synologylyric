<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouSiamzoneThailyric {
    private $site = 'http://www.siamzone.com';

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

        // http://www.siamzone.com/music/thailyric/index.php?mode=search&type=info&q=
        $keyword = sprintf("%s %s", $artist, $title);
        $searchUrl = sprintf(
            "%s/music/thailyric/index.php?mode=search&type=info&q=%s",
            $this->site, urlencode($keyword)
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

        $lyricUrl = sprintf("%s%s", $this->site, $id);

        $content = FujirouCommon::getContent($lyricUrl);
        if (!$content) {
            return false;
        }

        $prefix = '<div class="lyric">';
        $suffix = '</div>';
        $html = FujirouCommon::getSubString($content, $prefix, $suffix);
        $lyric = FujirouCommon::toOneLine($html);

        $lyric = str_replace('<br />', "\n", $lyric);
        $lyric = trim(strip_tags($lyric));

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $prefix = '<div id="music_thailyric_minisearch">';
        $suffix = '<div class="lyric">';
        $searchList = FujirouCommon::getSubString($content, $prefix, $suffix);

        if (empty($searchList)) {
            return false;
        }

        $prefix = '<div class="song">';
        $suffix = '</div>';
        $html = FujirouCommon::getSubString($searchList, $prefix, $suffix);
        if (empty($html)) {
            // failed to find song title and lyric page
            return false;
        }

        $pattern = '/<a href="[^"]+">([^<]+)<\/a>/';
        $title = FujirouCommon::getFirstMatch($html, $pattern);
        $pattern = '/<a href="([^"]+)">[^<]+<\/a>/';
        $url = FujirouCommon::getFirstMatch($html, $pattern);

        $prefix = '<div class="artist">';
        $suffix = '</div>';
        $html = FujirouCommon::getSubString($searchList, $prefix, $suffix);
        if (empty($html)) {
            // failed to find artist info
            return false;
        }

        $pattern = '/<a href="[^"]+">([^<]+)<\/a>/';
        $artist = FujirouCommon::getFirstMatch($html, $pattern);

        $item = array(
            'artist' => $artist,
            'title'  => $title,
            'id'     => $url,
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
                return false;
            }
        }
    }

    $module = 'FujirouSiamzoneThailyric';
//     $artist = 'มูซู';
    $artist = 'กระแต อาร์สยาม';
//     $title = 'รักแท้ไม่มีจริง';
    $title = 'ตื๊ด';

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

