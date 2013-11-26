<?php

class HomeController extends BaseController {

    /*
    |--------------------------------------------------------------------------
    | Default Home Controller
    |--------------------------------------------------------------------------
    |
    | You may wish to use controllers instead of, or in addition to, Closure
    | based routes. That's great! Here is an example controller method to
    | get you started. To route to this controller, just add the route:
    |
    |	Route::get('/', 'HomeController@showWelcome');
    |
    */

    public function redirectThem() {
        return Redirect::to("http://tenjava.com");
    }

    public function handlePayload() {
        if ($this->cidrMatch(Request::getClientIp(), "192.30.252.0/22")) {
            Log::info("Got payload " . Input::get("payload")); //TODO
            $json = json_decode(Input::get("payload"));
            $head = $json->head_commit;
            $username = $head->author->username;
            $github = new Github\Client();
            $github->authenticate(Config::get("private-secure.github-token"), null, Github\Client::AUTH_HTTP_TOKEN);
            $commit = $github->api('repo')->commits()->show('tenjavacontest', $json->repository->name, $head->id);
            Log::info(json_encode($commit));
            FlareBot::sendMessage("ten.java", FlareBot::COLOR . FlareBot::LIME . "Add: " . $commit['stats']['additions'] . FlareBot::COLOR . FlareBot::WHITE . " | " . FlareBot::COLOR . FlareBot::RED . "Del: " . $commit['stats']['deletions']);
        }
        return "Thanks.";
    }

    private function cidrMatch($ip, $cidr) { //thanks SO
        list($subnet, $mask) = explode('/', $cidr);

        if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
            return true;
        }

        return false;
    }

}
