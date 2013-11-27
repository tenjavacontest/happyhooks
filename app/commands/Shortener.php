<?php

use Requests;

class Shortener {
    public static function shortenGithubUrl($url, $name) {
        $data = array('url' => $url, 'code' => $name);
        $response = Requests::post('http://git.io', array(), $data);
        return $response->headers['Location'];
    }
} 