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
            $github = new Github\Client();
            $github->authenticate(Config::get("private-secure.github-token"), null, Github\Client::AUTH_HTTP_TOKEN);
            $commit = $github->api('repo')->commits()->show('tenjavacontest', $json->repository->name, $head->id);
            Log::info(json_encode($commit));
            $author = $commit['author']['login'];
            $gravatar = $commit['author']['gravatar_id'];
            $additions = $commit['stats']['additions'];
            $deletions = $commit['stats']['deletions'];

            $files = $commit['files'];
            $filesAdded = 0;
            $filesModified = 0;
            $filesRemoved = 0;
            foreach ($files as $file) {
                Log::info("Yay! " . $file['status'] . " was " . $file['filename']);
                if ($file['status'] == "added") {
                    $filesAdded++;
                } else if ($file['status'] == "modified" || $file['status'] == "changed") {
                    $filesModified++;
                } else {
                    $filesRemoved++;
                }
            }
            $filesSum = $filesAdded + $filesModified + $filesRemoved;
            $userRecord = DB::table("github_user_details")->where("username", $author)->first();
            if ($userRecord == null) {
                DB::table("github_user_details")->insert(array("username" => $author, "gravatar_id" => $gravatar));
            } else if ($userRecord->gravatar_id != $gravatar) {
                DB::table("github_user_details")->where("username", $author)->update(array("gravatar_id" => $gravatar));
            }

            $commitEntry = array(
                'repository' => $json->repository->name,
                'username' => $author,
                'commit_id' => $head->id,
                'new_files' => $filesAdded,
                'changed_files' => $filesModified,
                'removed_files' => $filesRemoved,
                'total_deletions' => $deletions,
                'total_additions' => $additions,
                'commit_message' => Str::limit($commit['commit']['message'], 252)
            );
            DB::table("commit_stats")->insert($commitEntry);
            $friendlyUrl = Shortener::shortenGithubUrl($commit['commit']['url'], "tenjava-" . substr($head->id, 0, 6));
            $message = $json->repository->name . " has just committed to their repo!" . FlareBot::BOLD . FlareBot::COLOR . FlareBot::GREEN . " " .
                       $filesSum . " file " . self::getWordForm($filesSum, "action") . FlareBot::BOLD . FlareBot::COLOR . " with a total of" .
                       FlareBot::COLOR . FlareBot::GREEN . FlareBot::BOLD . " " . $additions . " line " . self::getWordForm($additions, "addition")
                       . FlareBot::COLOR . FlareBot::BOLD . " and" . FlareBot::COLOR . FlareBot::RED . " " . $deletions . " line " . self::getWordForm($deletions, "deletion")
                       . FlareBot::COLOR . FlareBot::BOLD . ". $friendlyUrl";
            FlareBot::sendMessage("ten.java", $message);
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

    public static function getWordForm($num, $singular) {
        if ($num > 1 || $num == 0) {
            return str_plural($singular);
        }
        return $singular;
    }

}
