<?php

use Carbon\Carbon;

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

    public function __construct() {
        $this->afterFilter(function ($route, $x, $response) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        });
    }

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
            $displayName = $json->repository->name;

            if (!isset($author)) {
                $author = "Please_set_user.email (AKA " . $head->author->name . ")";
                $displayName = $author;
            }

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
                } else {
                    if ($file['status'] == "modified" || $file['status'] == "changed") {
                        $filesModified++;
                    } else {
                        $filesRemoved++;
                    }
                }
            }
            $filesSum = $filesAdded + $filesModified + $filesRemoved;
            if (isset($gravatar)) {
                $userRecord = DB::table("github_user_details")->where("username", $author)->first();

                if ($userRecord == null) {
                    DB::table("github_user_details")->insert(array("username" => $author, "gravatar_id" => $gravatar));
                } else {
                    if ($userRecord->gravatar_id != $gravatar) {
                        DB::table("github_user_details")->where("username", $author)->update(array("gravatar_id" => $gravatar));
                    }
                }
            }
            $commitMsg = Str::limit($commit['commit']['message'], 252);
            $commitEntry = array(
                'repository' => $json->repository->name,
                'username' => $author,
                'commit_id' => $head->id,
                'new_files' => $filesAdded,
                'changed_files' => $filesModified,
                'removed_files' => $filesRemoved,
                'total_deletions' => $deletions,
                'total_additions' => $additions,
                'commit_message' => $commitMsg,
                'created_at' => new DateTime()
            );
            DB::table("commit_stats")->insert($commitEntry);
            $friendlyUrl = Shortener::shortenGithubUrl($commit['html_url'], "tenjava-" . substr($head->id, 0, 6));
            $message = $displayName . ": \"$commitMsg\"." . FlareBot::BOLD . FlareBot::COLOR . FlareBot::GREEN . " " .
                $filesSum . " file " . self::getWordForm($filesSum, "action") . FlareBot::BOLD . FlareBot::COLOR . " with a total of" .
                FlareBot::COLOR . FlareBot::GREEN . FlareBot::BOLD . " " . $additions . " line " . self::getWordForm($additions, "addition")
                . FlareBot::COLOR . FlareBot::BOLD . " and" . FlareBot::COLOR . FlareBot::RED . FlareBot::BOLD . " " . $deletions . " line " . self::getWordForm($deletions, "deletion")
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

    public function getCommits() {
        $amount = Input::get("number");
        if ($amount == null || $amount < 1 || $amount > 100) {
            return self::getResponse(self::getError("Supply a valid ?number."));
        } else {
            $records = DB::table("commit_stats")->orderBy("created_at", "desc")->join('github_user_details', 'commit_stats.username', '=', 'github_user_details.username')->take($amount)->get();
            foreach ($records as $key => $val) {
                $carbon = new Carbon($val->created_at);
                $records[$key]->created_at = $carbon->diffForHumans();
            }
            return self::getResponse($records);

        }
    }

    public function getResponse($result) {
        if (Input::has("callback")) {
            return Response::json($result)->setCallback(Input::get('callback'));
        } else {
            return Response::json($result);
        }
    }

    private function getError($error) {
        return Response::json(array("error" => $error));
    }

    public static function getWordForm($num, $singular) {
        if ($num > 1 || $num == 0) {
            return str_plural($singular);
        }
        return $singular;
    }

}
