<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouGenius
{
    private $apiUrl = 'https://genius.com/api/search/multi';

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

    private function getQuery($artist, $title)
    {
        $special_chars = ["(", ")"];
        $stripped_artist = str_replace($special_chars, "", $artist);
        $stripped_title = str_replace($special_chars, "", $title);
        return urlencode("$stripped_artist $stripped_title");
    }

    public function search($handle, $artist, $title)
    {
        $count = 0;

        $url = $this->apiUrl;
        $query = $this->getQuery($artist, $title);
        $search_url = "$url?q=$query";

        $content = FujirouCommon::getContent($search_url);
        $obj = json_decode($content, true);
        $sections = $obj['response']['sections'];

        if (!$sections) {
            FujirouCommon::printMsg("Failed to get sections of search");
            FujirouCommon::printMsg($content);
            return 0;
        }

        $song_section = null;
        $top_hit_section = null;
        foreach ($sections as $section) {
            if ($section['type'] === 'top_hit') {
                $top_hit_section = $section;
            }
            if ($section['type'] === 'song') {
                $song_section = $section;
                break;
            }
        }

        if (!$song_section) {
            return $count;
        }
        $results = [];

        foreach ($song_section['hits'] as $hit) {
            array_push($results, $hit['result']);
        }

        if ($top_hit_section && count($results) === 0) {
            if (count($top_hit_section['hits']) > 0) {
                array_push($results, $top_hit_section['hits'][0]['result']);
            }
        }

        foreach ($results as $result) {
            $artist = $result['primary_artist']['name'];
            $title = $result['title'];
            $id = $result['url'];

            $handle->addTrackInfoToList($artist, $title, $id, '');
            $count += 1;
        }

        return $count;
    }

    public function get($handle, $id)
    {
        $result = array();
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return false;
        }

        $prefix = '<div class="lyrics">';
        $suffix = '</div>';
        $body = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$body) {
            FujirouCommon::printMsg("Failed to get content from .lyrics, try Lyrics__Root");

            $prefix = ' Lyrics__Root';
            $suffix = '<div class="SectionLeaderboard';
            $body = FujirouCommon::getSubString($content, $prefix, $suffix);
            if (!$body) {
                FujirouCommon::printMsg("Failed to get content from Lyrics__Root");
                FujirouCommon::printMsg($content);
                return false;
            }

            # remove prefix and suffix
            $body = preg_replace("/$prefix.*?>/", '', $body);
            $body = str_replace($suffix, '', $body);

            # add newline for ad block
            $body = str_replace('<div class="SidebarAd__Container', '<br/><div class="', $body);
        }
        if (!$body) {
            FujirouCommon::printMsg("Failed to get lyric content for parsing");
            return false;
        }

        $body = FujirouCommon::decodeHTML($body);
        $body = str_replace('<br/>', "\n", $body);

        // remove sharing button
        $body = preg_replace('/<button.*?<\/button>/', '', $body);
        $body = preg_replace('/<label.*?<\/label>/', '', $body);
        $body = preg_replace('/<div class="EmbedForm__Copy.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="ShareButtons_.*?<\/div>/', '', $body);
        $body = preg_replace('/<div class="LyricsEditExplainer__.*?<\/div>/', '', $body);

        $body = trim(strip_tags($body));

        $pattern = '<meta content="(.*?) – (.*?)" property="twitter:title" />';
        $artist = trim(FujirouCommon::getFirstMatch($content, $pattern));
        $artist = str_replace("\xc2\xa0", " ", $artist);
        $pattern = '<meta content=".*? – (.*?)" property="twitter:title" />';
        $title = trim(FujirouCommon::getFirstMatch($content, $pattern));
        $title = preg_replace('/[\x{200B}-\x{200D}]/u', '', $title);

        $lyric = sprintf(
            "%s\n\n%s\n\n\n%s",
            'lyric from Genius Lyrics',
            "$artist - $title",
            $body
        );

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function decodeUTF8($obj)
    {
        $tryDecodeFunc = function ($str) {
            if ($str) {
                $length = strlen($str);
                $temp = '';

                // if decode failed, remove character from end and decode again
                while (empty($temp) && $length > 0) {
                    $temp = utf8_decode(substr($str, 0, $length));

                    $jsonStr = json_encode($temp);
                    if (empty($jsonStr) || 'null' === $jsonStr) {
                        // will lead to json_encode() fail
                        $temp = '';
                    }
                    $length -= 1;
                }

                $str = $temp;
            }

            return $str;
        };

        return array_map($tryDecodeFunc, $obj);
    }
}
