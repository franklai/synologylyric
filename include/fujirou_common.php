<?php

class FujirouCommon
{
    /**
     * send request to given url, and return content
     */
    public static function getContent($url)
    {
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

    /**
     * Using Google Search API, and return the json of results
     */
    public static function searchFromGoogle($keyword, $siteUrl)
    {
        $googleWebSearchUrl = 'https://ajax.googleapis.com/ajax/services/search/web';

        $queryString = sprintf(
            "%s site:%s",
            $keyword, $siteUrl
        );

        $searchUrl = sprintf(
            "%s?v=1.0&q=%s",
            $googleWebSearchUrl, urlencode($queryString)
        );

        $content = self::getContent($searchUrl);

        $json = json_decode($content, TRUE);

        return $json['responseData']['results'];
    }

    /**
     * given regular expression, return the first matched string
     */
    public static function getFirstMatch($string, $pattern)
    {
        if (1 === preg_match($pattern, $string, $matches)) {
            return $matches[1];
        }
        return FALSE;
    }

    /**
     * given prefix and suffix, return the shortest matched string.
     * returned string including prefix and suffix
     */
    public static function getSubString($string, $prefix, $suffix)
    {
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

    /**
     * remove CR and LF from string
     */
    public static function toOneLine($string)
    {
        return str_replace(array("\n", "\r"), '', $string);
    }
}

// vim: expandtab ts=4
?>
