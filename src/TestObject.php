<?php

class TestObject
{
    private $items;

    public function __construct()
    {
        $this->items = array();
    }

    public function addLyrics($lyric, $id)
    {
        $this->lyric = $lyric;
        /*
        printf("\n");
        printf("song id: %s\n", $id);
        printf("\n");
        printf("== lyric ==\n");
        printf("%s\n", $lyric);
        printf("** END of lyric **\n\n");
        */
    }

    public function addTrackInfoToList($artist, $title, $id, $prefix)
    {
        /*
        printf("\n");
        printf("song id: %s\n", $id);
        printf("%s - %s\n", $artist, $title);
        printf("\n");
        printf("== prefix ==\n");
        printf("%s\n", $prefix);
        printf("** END of prefix **\n\n");
         */

        array_push($this->items, array(
            'artist' => $artist,
            'title' => $title,
            'id' => $id,
        ));
    }

    public function getItems()
    {
        return $this->items;
    }
    public function getFirstItem()
    {
        if (count($this->items) > 0) {
            return $this->items[0];
        } else {
            return false;
        }
    }
    public function getLyric()
    {
        return $this->lyric;
    }
}
