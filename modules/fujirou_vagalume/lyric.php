<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouVagalume {
    private $_site = 'http://www.vagalume.com.br';

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

        // http://www.vagalume.com.br/api/docs/letras/
        $searchUrl = sprintf(
            "%s/api/search.php?art=%s&mus=%s&nolyrics=1",
            $this->_site, urlencode($artist), urlencode($title)
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

        $apiQuery = sprintf(
            "%s/api/search.php?musid=%s",
            $this->_site, $id
        );

        $content = FujirouCommon::getContent($apiQuery);
        if (!$content) {
            return false;
        }

        $json = json_decode($content, true);

        if (!array_key_exists('mus', $json)
            || count($json['mus']) === 0) {
            return false;
        }

        $music = $json['mus'][0];

        if (!array_key_exists('text', $music)) {
            return false;
        }

        $lyric = $music['text'];

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $json = json_decode($content, true);

        if (!array_key_exists('art', $json)
            || !array_key_exists('mus', $json) || count($json['mus']) === 0) {
            return $result;
        }

        $art = $json['art'];
        $music = $json['mus'][0];

        $item = array(
            'artist' => $art['name'],
            'title'  => $music['name'],
            'id'     => $music['id'],
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

    $module = 'FujirouVagalume';
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

