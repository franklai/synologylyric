<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        require(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        require(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouKkbox
{
    private $site = 'http://www.kkbox.com';

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

        // http://tw.kkbox.com/search.php?word=%E6%96%B9%E5%A4%A7%E5%90%8C+%E6%84%9B%E6%84%9B%E6%84%9B&search=song&search_lang=
        // http://www.kkbox.com/tw/tc/search.php?search=mix&word=%E5%AE%89%E5%A6%AE%E6%9C%B5%E6%8B%89%20%E6%B0%B8%E7%84%A1%E5%B3%B6
        $keyword = sprintf("%s %s", $artist, $title);
        $searchUrl = sprintf(
            "%s/tw/tc/search.php?word=%s&search=song",
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

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return FALSE;
        }

        $prefix = '<div class="lyrics col-md-12">';
        $suffix = '</div>';

        $oneLineContent = FujirouCommon::toOneLine($content);

        $lyric = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        $lyric = str_replace('<br />', "\n", $lyric);

        $lyric = trim(strip_tags($lyric));
        $lyric = FujirouCommon::decodeHTML($lyric);
        $lyric = str_replace('                            ', "\n", $lyric);

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $prefix = '<td class="song-name"><a href="/';
        $suffix = '</tbody>';

        $oneLineContent = FujirouCommon::toOneLine($content);
        $searchResult = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        if (!$searchResult) {
            return $result;
        }

        $items = explode('</tr>', $searchResult);
        foreach ($items as $item) {
            $pattern = '/<td class="song-name"><a href="[^"]+"[^>]*>(.*)<\/a>/U';
            $title = FujirouCommon::getFirstMatch($item, $pattern);

            $pattern = '/<a href="\/tw\/tc\/artist[^"]+"[^>]*>(.*)<\/a>/U';
            $artist = FujirouCommon::getFirstMatch($item, $pattern);

            $pattern = '/<a href="(\/tw\/tc\/song[^"]+)".*title="歌詞">/';
            $url = FujirouCommon::getFirstMatch($item, $pattern);

            if (!$title || !$artist || !$url) {
                continue;
            }

            $item = array(
                'artist' => strip_tags($artist),
                'title'  => strip_tags($title),
                'id'     => sprintf("%s%s", $this->site, $url),
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
            printf("artist [%s]\n", $artist);
            printf("title [%s]\n", $title);
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

    $module = 'FujirouKkbox';
//     $artist = '盧廣仲';
//     $title = '無敵';
    $artist = '安妮朵拉';
    $title = '永無島';

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
        echo " ****************************\n";
        echo " *** Failed to find lyric ***\n";
        echo " ****************************\n";
    }
}
// vim: expandtab ts=4

