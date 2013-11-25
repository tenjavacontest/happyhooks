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
			Log::info("Got payload."); //TODO
                        FlareBot::sendMessage("ten.java", "Wow, such commit");
		}
		return "Thanks.";
	}
	
	private function cidrMatch($ip, $cidr) { //thanks SO
		list($subnet, $mask) = explode('/', $cidr);

		if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1) ) == ip2long($subnet))
		{ 
			return true;
		}

		return false;
	}

}
