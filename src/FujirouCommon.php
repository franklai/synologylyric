<?php

class FujirouCommon
{
    /**
     * send request to given url, and return content
     */
    public static function getContent($url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:82.0) Gecko/20100101 Firefox/82.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($curl, CURLOPT_VERBOSE, true);

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

        $json = json_decode($content, true);

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
        return false;
    }

    /**
     * given regular expression, return all matched first group in an array
     */
    public static function getAllFirstMatch($string, $pattern)
    {
        $ret = preg_match_all($pattern, $string, $matches);
        if ($ret > 0) {
            return $matches[1];
        } else {
            return $ret;
        }
    }

    /**
     * given regular expression, return all matched groups
     */
    public static function getAllMatches($string, $pattern)
    {
        $ret = preg_match_all($pattern, $string, $matches);
        if ($ret > 0) {
            return $matches;
        } else {
            return $ret;
        }
    }

    /**
     * given prefix and suffix, return the shortest matched string.
     * returned string including prefix and suffix
     */
    public static function getSubString($string, $prefix, $suffix)
    {
        $start = strpos($string, $prefix);
        if ($start === false) {
            return null;
        }

        $end = strpos($string, $suffix, $start);
        if ($end === false) {
            return null;
        }

        if ($start >= $end) {
            return null;
        }

        return substr($string, $start, $end - $start + strlen($suffix));
    }

    /**
     * remove CR and LF from string
     */
    public static function toOneLine($string)
    {
        $string = str_replace(array("\n", "\r"), '', $string);
        return preg_replace('/>\s+</', '><', $string);
    }

    /**
     * decode HTML entity using UTF-8 encoding
     */
    public static function decodeHTML($string)
    {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
    }

    public static function printMsg($msg)
    {
        if (!defined('DEBUG')) {
            return;
        }
        if (is_array($msg)) {
            print_r($msg);
        } else {
            echo "\ndebug msg: $msg\n";
        }
    }
}

// vim: expandtab ts=4
