<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouLyricsInTh {
    private $site = 'http://lyrics.in.th';

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

        // http://lyrics.in.th/?s=Kiss+Me+Five+%E0%B8%84%E0%B8%B4%E0%B8%94%E0%B8%81%E0%B9%88%E0%B8%AD%E0%B8%99%E0%B8%97%E0%B8%B4%E0%B9%89%E0%B8%87
        $keyword = sprintf("%s %s", $artist, $title);
        $searchUrl = sprintf(
            "%s/?s=%s",
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

        $lyricUrl = sprintf("%s", $id);

        $content = FujirouCommon::getContent($lyricUrl);
        if (!$content) {
            return false;
        }

        $prefix = '<span id="more-';
        $suffix = '<b><i>';
        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);
        $lyric = str_replace('<p>', "\n", $lyric);
        $lyric = trim(strip_tags($lyric));

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function getEntryTitle($content) {
        $prefix = '<h2 class="entry-title">';
        $suffix = '</h2>';

        return FujirouCommon::getSubString($content, $prefix, $suffix);
    }

    private function parseSearchResult($content) {
        $result = array();
        $title = '';
        $artist = '';

        // to one line
        $content = FujirouCommon::toOneLine($content);

        while (true) {
            $entryTitle = $this->getEntryTitle($content);

            if ($entryTitle == $content) {
                return false;
            }

            $pattern = '/<a href="([^"]+)"[^>]*>\s*เนื้อเพลง ([^<]+) &#8211; ([^<]+)</';
            if (1 === preg_match($pattern, $entryTitle, $matches)) {
                $url = $matches[1];
                $title = $matches[2];
                $artist = $matches[3];
                break;
            }

            $pos = strpos($content, $entryTitle);
            $content = substr($content, $pos + strlen($entryTitle));
        }

        if (empty($url) || empty($artist) || empty($title)) {
            return false;
        }

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

    $module = 'FujirouLyricsInTh';
//     $artist = 'มูซู';
//     $artist = 'อิทธิ พลางกูร';
    $artist = 'แอน ธิติมา, ศิรศักดิ์';
//     $title = 'รักแท้ไม่มีจริง';
//     $title = 'เวลาที่เหลืออยู่';
    $title = 'แค่คนที่รักเธอ';

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

