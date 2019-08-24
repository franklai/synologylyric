<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouKkbox
{
    private $site = 'https://www.kkbox.com';

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

        $prefix = "<script type='application/ld+json'>";
        $suffix = '</script>';

        $ld_json_text = FujirouCommon::getSubString($content, $prefix, $suffix);
        $ld_json = json_decode(strip_tags($ld_json_text), true);

        $title = $ld_json['name'];
        $artist = $ld_json['byArtist']['name'];
        $body = $ld_json['recordingOf']['lyrics']['text'];
        $body = trim(str_replace("\r\n", "\n", $body));

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from KKBOX',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content) {
        $result = array();

        // only find first item
        $prefix = '<td class="song-data">';
        $suffix = '</td>';

        $oneLineContent = FujirouCommon::toOneLine($content);
        $searchResult = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        if (!$searchResult) {
            return $result;
        }

        $pattern = '/<a class="song-title" href="[^"]+" title="(.+)">/U';
        $title = FujirouCommon::getFirstMatch($searchResult, $pattern);

        $pattern = '/<a href="\/tw\/tc\/artist\/[^"]+" title="(.+)">/U';
        $artist = FujirouCommon::getFirstMatch($searchResult, $pattern);

        $pattern = '/<a class="song-title" href="(\/tw\/tc\/song[^"]+)" title=".+">/U';
        $url = FujirouCommon::getFirstMatch($searchResult, $pattern);

        if (!$title || !$artist || !$url) {
            return $result;
        }

        $item = array(
            'artist' => strip_tags($artist),
            'title'  => strip_tags($title),
            'id'     => sprintf("%s%s", $this->site, $url),
            'partial'=> ''
        );

        array_push($result, $item);

        return $result;
    }
}

// vim: expandtab ts=4
