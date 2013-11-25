<?php
use Requests;

class FlareBot {
    const BOLD = "!~";
    const COLOR = ":~";

    public static function sendMessage($channel, $message) {
        if (starts_with($channel, "#")) {
            $channel = substr($channel, 1);
        }
        Requests::get(Config::get("private-secure.flarebot-url") . "&channel=" . $channel . "&message=" . $message);
    }
} 
