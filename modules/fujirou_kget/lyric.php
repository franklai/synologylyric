<?php

class FujirouKget {
    private $searchPrefix = 'http://www.kget.jp/result/index.aspx';
    private $lyricPrefix = 'http://lyric.kget.jp';
    private $realLyricPrefix = 'http://lyric.kget.jp/iframe/sendlyric.aspx';

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

        $encodedArtist = mb_convert_encoding($artist, 'SJIS', 'UTF-8');
        $encodedTitle = mb_convert_encoding($title, 'SJIS', 'UTF-8');

        // search
        // http://www.kget.jp/result/index.aspx?c=0&a=perfume&t=%83V%81%5B%83N%83%8C%83b%83g%83V%81%5B%83N%83%8C%83b%83g&b=&f=&x=0&y=0
        $searchUrl = sprintf(
            "%s?c=0&a=%s&t=%s",
            $this->searchPrefix, urlencode($encodedArtist), urlencode($encodedTitle)
        );

        $content = $this->getContent($searchUrl);

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
        $content = str_replace(array("\n", "\r"), '', $content);

        $content = mb_convert_encoding($content, 'UTF-8', 'SJIS');

        $resultTable = $this->getSubString($content, '<table class="result"', '</table>');

        $resultTable = str_replace('</td></tr></table>', '', $resultTable);
        $resultItems = explode('</td><td>', $resultTable);

        if (count($resultItems) !== 6) {
            return FALSE;
        }

        // 1. title
        // 2. artist
        // 3. lyricist
        // 4. composer
        // 5. partial lyric
        $pattern = '/<a href="(.*)">.*<\/a>/';
        $lyricUrl = $this->getFirstMatch($resultItems[1], $pattern);

        $pattern = '/<a href=".*">(.*)<\/a>/';
        $title = $this->getFirstMatch($resultItems[1], $pattern);

        $pattern = '/<a href=".*">(.*)<\/a>/';
        $artist = $this->getFirstMatch($resultItems[2], $pattern);

        $partial = $this->getFirstMatch($resultItems[5], $pattern);

        return array(
            'artist' => $artist,
            'title' => $title,
            'id' => $lyricUrl,
            'partial' => $partial
        );
    }

    public function get($handle, $id) {
        $result = array();
        $lyric = '';

        // id should be url of lyric
        if (strchr($id, $this->lyricPrefix) === FALSE) {
            return FALSE;
        }

        $content = $this->getContent($id);
        if (!$content) {
            return FALSE;
        }

        $pattern = '/lyric.swf\?sn=([a-zA-Z0-9\/]+)/';
        $sn = $this->getFirstMatch($content, $pattern);
        if ($sn === FALSE) {
            return FALSE;
        }

        $lyricUrl = sprintf(
            "%s?sn=%s",
            $this->realLyricPrefix,
            $sn
        );

        $content = $this->getContent($lyricUrl);
        if (!$content) {
            return FALSE;
        }

        $content = mb_convert_encoding($content, 'UTF-8', 'SJIS');

        $lyric = substr($content, strlen('lyric='));

        $handle->addLyrics($lyric, $id);

        return TRUE;
    }

    private function getContent($url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

        curl_setopt($curl, CURLOPT_VERBOSE, TRUE);

        curl_setopt($curl, CURLOPT_URL, $url);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    private function getFirstMatch($string, $pattern) {
        if (1 === preg_match($pattern, $string, $matches)) {
            return $matches[1];
        }
        return FALSE;
    }

    private function getSubString($string, $prefix, $suffix) {
        $start = strpos($string, $prefix);
        if ($start === FALSE) {
            echo "cannot find prefix, string:[$string], prefix[$prefix]\n";
            return $string;
        }

        $end = strpos($string, $suffix, $start);
        if ($end === FALSE) {
            echo "cannot find suffix\n";
            return $string;
        }

        if ($start >= $end) {
            return $string;
        }

        return substr($string, $start, $end - $start + strlen($suffix));
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

