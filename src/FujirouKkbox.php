<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouKkbox
{
    private $site = 'https://www.kkbox.com';

    public function __construct()
    {
    }

    public function getLyricsList($artist, $title, $info)
    {
        return $this->search($info, $artist, $title);
    }
    public function getLyrics($id, $info)
    {
        return $this->get($info, $id);
    }

    public function search($handle, $artist, $title)
    {
        $count = 0;

        // https://www.kkbox.com/tw/tc/search.php?word=%E8%8C%84%E5%AD%90%E8%9B%8B+%E6%B5%AA%E6%B5%81%E9%80%A3
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

    public function get($handle, $id)
    {
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return false;
        }

        $prefix = '<script type="application/ld+json">';
        $suffix = '</script>';

        $ld_json_text = FujirouCommon::getSubString($content, $prefix, $suffix);
        $ld_json = json_decode(strip_tags($ld_json_text), true);

        if (!array_key_exists('name', $ld_json)) {
            $pos_first = strpos($content, $prefix);
            $pos_second = strpos($content, $prefix, $pos_first + 1);
            if ($pos_second === false) {
                return false;
            }

            $ld_json_text = FujirouCommon::getSubString(substr($content, $pos_second), $prefix, $suffix);
            $ld_json = json_decode(strip_tags($ld_json_text), true);
        }

        $title = $ld_json['name'];
        $artist = $ld_json['byArtist']['name'];
        if (!array_key_exists('recordingOf', $ld_json)) {
            $body = 'Currently there are no lyrics for this song.';
        } else {
            $body = $ld_json['recordingOf']['lyrics']['text'];
            $body = trim(str_replace("\r\n", "\n", $body));
        }

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from KKBOX',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content)
    {
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
            'title' => strip_tags($title),
            'id' => sprintf("%s%s", $this->site, $url),
            'partial' => '',
        );

        array_push($result, $item);

        return $result;
    }
}

// vim: expandtab ts=4
