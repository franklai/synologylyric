<?php
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

class FujirouMetroLyrics
{
    private $_site = 'https://www.metrolyrics.com';
    private $_apiSite = 'http://api.metrolyrics.com';
    private $_apiKey = '196f657a46afb63ce3fd2015b9ed781280337ea7';

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

        $keyword = sprintf("%s %s", $artist, $title);

        // http://www.metrolyrics.com/api/v1/multisearch/all/X-API-KEY/196f657a46afb63ce3fd2015b9ed781280337ea7?find=taylor+swift+love+stor
        // http://api.metrolyrics.com/v1//multisearch/all/X-API-KEY/196f657a46afb63ce3fd2015b9ed781280337ea7/format/json?find=taylor+swift+fifteen&theme=desktop
        // http://api.metrolyrics.com/v1/
        $searchUrl = sprintf(
//             "%s/v1/search/artistsong/artist/%s/song/%s/X-API-KEY/%s/format/json",
            //             "%s/v1/search/artistsong?artist=%s&song=%s&X-API-KEY=%s&format=json",
            "%s/v1//multisearch/all/X-API-KEY/%s/format/json?find=%s",
            $this->_apiSite, $this->_apiKey, rawurlencode(sprintf('%s %s', $artist, $title))
//             $this->_site, rawurlencode($artist), rawurlencode($title), $this->_apiKey
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

    private function remove_noise($lyric)
    {
        $items = array(
            array(
                'prefix' => "<!--WIDGET - RELATED-->",
                'suffix' => "<!-- Second Section -->\n",
            ),
            array(
                'prefix' => "<!--WIDGET - PHOTOS-->",
                'suffix' => "<!-- Third Section -->\n",
            ),
            array(
                'prefix' => '<div id="mid-song-discussion"',
                'suffix' => "<span class=\"label\">See all</span>\n</a>\n</div>",
            ),
            array(
                'prefix' => '<p class="writers">',
                'suffix' => '</sd-lyricbody>',
            ),
        );

        foreach ($items as $item) {
            $prefix = $item['prefix'];
            $suffix = $item['suffix'];
            $noise = FujirouCommon::getSubString($lyric, $prefix, $suffix);
            if ($noise !== $lyric) {
                $lyric = str_replace($noise, '', $lyric);
            }
        }

        return $lyric;
    }

    public function get($handle, $id)
    {
        $lyric = '';

        $content = FujirouCommon::getContent($id);
        if (!$content) {
            return false;
        }

        $prefix = '<sd-lyricbody id="lyrics-body">';
        $suffix = '</sd-lyricbody>';

        $lyric = FujirouCommon::getSubString($content, $prefix, $suffix);
        if (!$lyric) {
            return false;
        }
        $lyric = $this->remove_noise($lyric);

        $lyric = str_replace('<br />', "\n", $lyric);
        $lyric = str_replace("<p class='verse'>", "\n\n", $lyric);
        $lyric = str_replace("\n\n\n", "\n\n", $lyric);
        $lyric = trim(strip_tags($lyric));
        $lyric = FujirouCommon::decodeHTML($lyric);

        // remove extra ad line
        $pattern = '/\[ From: http:\/\/www.metrolyrics.com\/.*.html \]/';
        $lyric = preg_replace($pattern, '', $lyric);
        $pattern = "/[ \t]+\n/";
        $lyric = preg_replace($pattern, '', $lyric);

        $pattern = '/"musicSongTitle":"(.*?)"/';
        $title = FujirouCommon::getFirstMatch($content, $pattern);

        $pattern = '/"musicArtistName":"(.*?)"/';
        $artist = FujirouCommon::getFirstMatch($content, $pattern);

        $lyric = sprintf(
			"%s\n\n%s\n\n\n%s",
			'lyric from MetroLyrics',
			"$artist - $title",
			$lyric
		);

        $handle->addLyrics($lyric, $id);

        return true;
    }

    private function parseSearchResult($content)
    {
        $result = array();

        $json = json_decode($content, true);

        if (!$json) {
            return $result;
        }

        if (!array_key_exists('results', $json) || count($json['results']) <= 0) {
            return $result;
        }
        if (!array_key_exists('songs', $json['results']) || count($json['results']['songs']) <= 0) {
            return $result;
        }

        $songs = $json['results']['songs'];
        if (!array_key_exists('d', $songs) || count($songs['d']) <= 0) {
            return $result;
        }

        $item = $songs['d'][0];

        $infos = explode('<br />', $item['p']);
        $artist = strip_tags($infos[0]);
        $title = strip_tags($infos[1]);

        $item = array(
            'artist' => $artist,
            'title' => $title,
            'id' => sprintf("%s/%s", $this->_site, $item['u']),
            'partial' => '',
        );

        array_push($result, $item);

        return $result;
    }
}
// vim: expandtab ts=4
