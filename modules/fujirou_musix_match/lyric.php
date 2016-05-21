<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        include_once(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        include_once(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouMusixMatch{
    private $_site = 'https://www.musixmatch.com';

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

        // https://www.musixmatch.com/ws/1.1/macro.search?format=json&q=linkin%20park&page_size=4
        $other_params = array(
            'track_fields_set=community_track_search',
            'artist_fields_set=community_artist_search',
            'app_id=community-app-v1.0'
        );
        $searchUrl = sprintf(
            "%s/ws/1.1/macro.search?format=json&q=%s&page_size=4&%s",
            $this->_site, rawurlencode(sprintf('%s %s', $title, $artist)), implode('&', $other_params)
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

        $url = sprintf("%s/lyrics/%s", $this->_site, rawurlencode($id));

        $content = FujirouCommon::getContent($url);
        if (!$content) {
            return false;
        }

        $prefix = 'var __mxmState = ';
        $suffix = ';</script>';

        $json_string = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$json_string || $json_string === $content) {
            return false;
        }

        $json_string = str_replace($prefix, '', $json_string);
        $json_string = str_replace($suffix, '', $json_string);

        $json = json_decode($json_string, true);
        if (!$json) {
            return false;
        }

        if (!isset($json['page']['lyrics']['lyrics']['body'])) {
            return false;
        }

        $body = $json['page']['lyrics']['lyrics']['body'];
        $title = $json['page']['track']['name'];
        $artist = $json['page']['track']['artistName'];

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from musixmatch',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        $json = json_decode($content, TRUE);

        if (!$json) {
            return $result;
        }

        if (!isset($json['message']['body']['macro_result_list']['track_list'])) {
            return $result;
        }

        $tracks = $json['message']['body']['macro_result_list']['track_list'];
        if (count($tracks) === 0) {
            return $result;
        }

        $item = $tracks[0]['track'];

        $title = $item['track_name'];
        $artist = $item['artist_name'];
        $vanity_id = $item['commontrack_vanity_id'];

        $item = array(
            'artist' => $artist,
            'title'  => $title,
            'id'     => $vanity_id,
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

    $module = 'FujirouMusixMatch';
//     $artist = 'pitbull';
//     $title = 'We Are One (Ole Ola)';
//     $title = 'red (original demo version)';
//     $title = 'album red';

//     $artist = 'Usher';
//     $title = 'Lovers & Friends';
//     $artist = 'taylor swift';
//     $title = 'style';
//     $artist = 'rihanna';
//     $title = 'work';
    $artist = 'CHiCo with HoneyWorks';
    $title = 'プライド革命';


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

