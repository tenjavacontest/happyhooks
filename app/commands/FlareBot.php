<?php
use Requests;

class FlareBot {
    const BOLD = "!~";
    const COLOR = ":~";
    const WHITE = 0;
    const BLACK = 1;
    const NAVY = 2;
    const GREEN = 3;
    const RED = 4;
    const MAROON = 5;
    const PURPLE = 6;
    const OLIVE = 7;
    const YELLOW = 8;
    const LIME = 9;
    const TEAL = 10;
    const AQUA = 11;
    const BLUE = 12;
    const FUCHSIA = 13;
    const GRAY = 14;
    const SILVER = 15;

    public static function sendMessage($channel, $message) {
        if (starts_with($channel, "#")) {
            $channel = substr($channel, 1);
        }
        Requests::get(Config::get("private-secure.flarebot-url") . "&channel=" . $channel . "&message=" . $message);
    }
} 
