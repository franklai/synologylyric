<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouMusixMatch
{
    private $sitePrefix = 'https://www.musixmatch.com';

    public function __construct()
    {
        if (getenv('GITHUB_ACTIONS')) {
            if (!defined('debug')) {
                define('debug', true);
            }
        }
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

        $searchUrl = sprintf(
            "%s/search/%s",
            $this->sitePrefix, rawurlencode(sprintf('%s %s', $title, $artist))
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

        return $count;
    }

    public function get($handle, $id)
    {
        $lyric = '';

        $url = sprintf("%s%s", $this->sitePrefix, $id);

        $content = FujirouCommon::getContent($url);
        if (!$content) {
            FujirouCommon::printMsg("Failed to get content of $url");
            return false;
        }

        $length = strlen($content);
        FujirouCommon::printMsg("content length is $length of $url");

        $prefix = 'var __mxmState = ';
        $suffix = ';</script>';

        $json_string = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$json_string || $json_string === $content) {
            if ($this->isBlocked($content)) {
                throw new BlockedException("musixmatch");
            }
            FujirouCommon::printMsg("Failed to find sub string of $url");
            FujirouCommon::printMsg($content);
            return false;
        }

        $json_string = str_replace($prefix, '', $json_string);
        $json_string = str_replace($suffix, '', $json_string);

        $json = json_decode($json_string, true);
        if (!$json) {
            FujirouCommon::printMsg("Failed to decode json of $url");
            return false;
        }

        if (!isset($json['page']['lyrics']['lyrics']['body'])) {
            FujirouCommon::printMsg("Failed to decode json of $url");
            FujirouCommon::printMsg($json);
            return false;
        }

        $body = $json['page']['lyrics']['lyrics']['body'];
        $body = str_replace("′", "'", $body);
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

    private function parseSearchResult($content)
    {
        $result = array();

        $prefix = '<a class="title" ';
        $suffix = '</div></li>';
        $block = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$block) {
            if ($this->isBlocked($content)) {
                throw new BlockedException("musixmatch");
            }
            FujirouCommon::printMsg("Failed to find block from contet");
            FujirouCommon::printMsg($content);
            return $result;
        }

        $pattern = '/<a class="title" .*?><span.*?>(.*?)<\/span><\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            FujirouCommon::printMsg("Failed to find title from block");
            FujirouCommon::printMsg($block);
            return $result;
        }
        $title = FujirouCommon::decodeHTML($value);

        $pattern = '/<a class="artist".*?>(.*?)<\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            FujirouCommon::printMsg("Failed to find artist from block");
            FujirouCommon::printMsg($block);
            return $result;
        }
        $artist = FujirouCommon::decodeHTML($value);

        $pattern = '/<a class="title" href="(.+?)".*?><span.*?>.*?<\/span><\/a>/';
        $value = FujirouCommon::getFirstMatch($block, $pattern);
        if (!$value) {
            FujirouCommon::printMsg("Failed to find id from block");
            return $result;
        }
        $id = $value;

        $item = array(
            'artist' => $artist,
            'title' => $title,
            'id' => $id,
            'partial' => '',
        );

        array_push($result, $item);

        return $result;
    }

    private function isBlocked($content)
    {
        return strpos($content, "We detected that your IP is blocked") > 0;
    }
}
