<?php
if (!class_exists('FujirouCommon')) {
    if (file_exists(__DIR__.'/fujirou_common.php')) {
        require(__DIR__.'/fujirou_common.php');
    } else if (file_exists(__DIR__.'/../../include/fujirou_common.php')) {
        require(__DIR__.'/../../include/fujirou_common.php');
    }
}

class FujirouDirectLyrics {
    private $sitePrefix = 'http://www.directlyrics.com';
    private $siteHost = 'www.directlyrics.com';

    public function __construct() {
    }

    public function getLyricsList($artist, $title, $info) {
        return $this->search($info, $artist, $title);
    }
    public function getLyrics($id, $info) {
        return $this->get($info, $id);
    }

    public function search($handle, $artist, $title) {
        // using google search
        $count = 0;

        $keyword = sprintf("%s %s", $artist, $title);
        $results = FujirouCommon::searchFromGoogle($keyword, $this->siteHost);

        if (count($results) > 0) {
            if ($this->fillSearchResult($handle, $results[0])) {
                $count = 1;
            } else {
                // search again 
                // 1. remove "(...)" from title
                $keyword = '';

                $pos = strpos($title, '(');
                if ($pos !== FALSE) {
                    return $this->search($handle, $artist, substr($title, 0, $pos));
                }
            }
        }

        return $count;
    }

    public function get($handle, $id) {
        $result = array();
        $lyric = '';

        // id should be url of lyric
        if (strchr($id, $this->sitePrefix) === FALSE) {
            return FALSE;
        }

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return FALSE;
        }

        $oneLineContent = FujirouCommon::toOneLine($content);

        $prefix = '<div id="lyricsContent"><p>';
        $suffix = '</p></div>';
        $rawLyric = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);
        if (!$rawLyric) {
            return FALSE;
        }

        // convert html entity back to alphabet
        $lyric = html_entity_decode($rawLyric, ENT_QUOTES, 'UTF-8');

        // remove prefix and suffix
        $lyric = str_replace($prefix, "", $lyric);
        $lyric = str_replace($suffix, "", $lyric);

        // replace "<br>" and "</p><p>"
        $lyric = str_replace("<br>", "\n", $lyric);
        $lyric = str_replace("</p><p>", "\n\n", $lyric);

        $lyric = trim($lyric);

        $ogTitle = $this->getOgTitle($content);

        if (!empty($ogTitle)) {
            $lyric = sprintf("%s\n\n%s", $ogTitle, $lyric);
        }

        $handle->addLyrics($lyric, $id);

        return TRUE;
    }

    private function fillSearchResult($handle, $result) {
        $id = $result['unescapedUrl'];
        $rawTitle = $result['titleNoFormatting'];

        if (strpos($id, 'lyrics.html') === FALSE) {
            // search result is not lyric page
            return FALSE;
        }

        $items = explode(' - ', $rawTitle);

        $title = $rawTitle;
        $artist = '';
        if (count($items) === 2) {
            $artist = $items[0];
            $title = str_replace(' LYRICS', '', $items[1]);
        }

        $handle->addTrackInfoToList(
            $artist,
            $title,
            $id,
            ''
        );

        return TRUE;
    }

    function getOgTitle($content) {
        $pattern = '/<meta property="og:title" content="(.+)">/';

        return FujirouCommon::getFirstMatch($content, $pattern);
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

    $module = 'FujirouDirectLyrics';
    $artist = 'Taylor Swift';
    $title = 'back to december';
//     $title = 'red (original demo version)';
//    $title = 'album red';


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

    if (FALSE) {
        $url = 'http://www.directlyrics.com/taylor-swift-love-story-lyrics.html';

        echo "\nTest get ability only\n";
        echo "  test url: $url\n";
        echo "\n";

        $obj->get($testObj, $url);
    }
}

// vim: expandtab ts=4
?>

