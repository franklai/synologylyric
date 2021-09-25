<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouMojim
{
    private $site = 'https://mojim.com';

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

        // https://mojim.com/fifteen%20taylor%20swift.html
        $keyword = sprintf("%s %s", $title, $artist);
        $searchUrl = sprintf(
            "%s/%s.html?u4",
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
        $content = $this->convertUnicodePoint($content);

        $artistPrefix = "<dl id='fsZx1'";
        $artistSuffix = "<br /><br />";
        $artist = FujirouCommon::getSubString($content, $artistPrefix, $artistSuffix);
        $artist = trim(strip_tags($artist));

        $prefix = "<dt id='fsZx2'";
        $suffix = '</dl>';
        $raw = FujirouCommon::getSubString($content, $prefix, $suffix);
        $raw = FujirouCommon::toOneLine($raw);

        $pattern = "/<dt id='fsZx2'.*?>(.+?)<br/";
        $title = FujirouCommon::getFirstMatch($raw, $pattern);
        $title = trim(strip_tags($title));

        $lyricPrefix = '<br /><br />';
        $body = FujirouCommon::getSubString($raw, $lyricPrefix, $suffix);
        $body = $this->filterAd($body);
        $body = $this->filterThank($body);
        $body = str_replace('<br />', "\n", $body);
        $body = strip_tags($body);
        $body = trim($body);

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from Mojim.com',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function filterAd($content)
    {
        $items = explode('<br />', $content);
        $output = [];
        foreach ($items as $item) {
            if (strpos($item, "Mojim.com") !== false) {
                continue;
            }
            $output[] = $item;
        }
        return implode("<br />", $output);
    }

    private function filterThank($content)
    {
        return $content;
    }

    private function convertUnicodePoint($value)
    {
        $pattern = "/(&#\d+)/";
        $replaced = preg_replace($pattern, "\\1;", $value);
        return FujirouCommon::decodeHTML($replaced);
    }

    private function parseSearchResult($content)
    {
        $result = array();

        // only find first item
        $prefix = '<dl class="mxsh_dl0" >';
        $suffix = '</dl>';

        $oneLineContent = FujirouCommon::toOneLine($content);
        $searchResult = FujirouCommon::getSubString($oneLineContent, $prefix, $suffix);

        if (!$searchResult) {
            return $result;
        }

        // class="mxsh_ss3"><a href="/usy105842x25x17.htm" title="Lyrics 猫背"
        $pattern = '/class="mxsh_ss3"><a href="([^"]+)" *title="Lyrics [^"]+" *>/';
        $url = FujirouCommon::getFirstMatch($searchResult, $pattern);

        $pattern = '/class="mxsh_ss3"><a href="[^"]+" *title="Lyrics ([^"]+)" *>/';
        $title = FujirouCommon::getFirstMatch($searchResult, $pattern);

        $pattern = '/class="mxsh_ss3"><a href="[^"]+" *title="Lyrics [^"]+" *>(.+?)<\/a>/';
        $raw_partial = FujirouCommon::getFirstMatch($searchResult, $pattern);

        if (!$title || !$url) {
            return $result;
        }

        $item = array(
            'artist' => '',
            'title' => $title,
            'id' => sprintf("%s%s", $this->site, $url),
            'partial' => strip_tags($raw_partial),
        );

        array_push($result, $item);

        return $result;
    }
}

// vim: expandtab ts=4
