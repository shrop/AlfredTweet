<?php

	// Require the twitter oauth library
	require_once('twitteroauth.php');
	
	// Split the input to see what the first word is.
	// Done to check and see if a function is being called.
	$input = explode(" ", $argv[1]);
	
	if ($input[0] == "help" && !isset($input[1])) {
		$pin = exec("tail pin.txt");
		
		if (!$pin) { echo "The first step to setting up Tweet with Alfred is to authenticate this application with Twitter. To do that, use the setup command for this extension. \re.g. tw setup. \r\rAfter you authenticate, Twitter will provide you with a pin number to authenticate the application. Use the pin command to enter it. \re.g. tw pin <number>"; }
		else {
			$auth = check_auth();
			
			if ($auth == false) { echo "The second step to setting up Tweet with Alfred is to enter the pin number you received from Twitter. Do this using the pin command. \re.g. tw pin <number>"; }
			else {
				echo "Usernames do not require the @ for DM's, follow, unfollow, block, or unblock\r\r";
				echo "tw setup - Setup extension\r";
				echo "tw pin <number> - Save pin number\r";
				echo "tw <tweet> - Send tweet\r";
				echo "tw tweets - Last 5 tweets in timeline\r";
				echo "tw mentions - Last 5 Mentions\r";
				echo "tw dm <user> <message> - Send DM\r";
				echo "tw info <user> - Get User Info\r";
				echo "tw follow <user> - Follow user\r";
				echo "tw unfollow <user> - Unfollow user\r";
				echo "tw block <user> - Block user\r";
				echo "tw unblock <user> - Unblock user\r";
				echo "tw search <term> - Recent 5 matches";
			} //end else ($auth is set)
			
		} //end else !$pin
		
	} //end if input == help
	
	else {
		
		// Grab application keys
		$appkey1 = exec("curl http://cl.ly/081H2l3s0i0s0y2r3z30 | grep '<code>'");
		$appkey2 = exec("curl http://cl.ly/3g2d2O1S2s120R2m3p3u | grep '<code>'");

		$appkey1 = preg_replace('/<[a-z]+>/', '', $appkey1);
		$appkey2 = preg_replace('/<[a-z]+>/', '', $appkey2);
		$appkey1 = trim(preg_replace('/<\/[a-z]+>/', '', $appkey1));
		$appkey2 = trim(preg_replace('/<\/[a-z]+>/', '', $appkey2));

		if ($appkey1 == "") { exit("There seems to have been an error while attempting to set the application keys. Please try again."); }
		if ($appkey2 == "") { exit("There seems to have been an error while attempting to set the application keys. Please try again."); }
		
		// setup twitter
		if ($input[0] == "setup") {
		
			// Create a new instance
			$authtweet = new TwitterOAuth($appkey1, $appkey2);
		
			// Generate request token and open url to authenticate
			$token = $authtweet->getRequestToken("oob");
			$url =  $authtweet->getAuthorizeUrl($token);
			$result = system("open \"$url\"");
		
			// Set counter to limit wait looping
			$inc = 0;
		
			// Wait until the pin is set before continuing
			do {
			
				// Check the number of times the loop has executed. At 
				// 50 loops, assuming that the script has errored or
				// timed out, or other bad things are brewing.
				if ($inc > 50) {
					break;
				}
			
				// Check for pin value
				$pin = exec("tail pin.txt");
				$pin = str_replace("\n", "", $pin);
			
				// Increment loop, sleep 5 seconds, try again.
				$inc++;
				sleep(5);
			
			}
			while($pin == "");
		
			// This should only occur if something bad happened and the request timed out
			// or errored out or something. Using to kill this script execution.
			if ($inc > 50 && $pin == "") {
				echo "Twitter setup timed out waiting for a pin number. Please try again.";
			}
		
			else {
		
				// Request the access token
				$access_token = $authtweet->getAccessToken($pin);

				// Write oauth tokens to auth.xml
				$auth_file = new SimpleXMLElement("<oauth></oauth>");
				$auth_file->addChild("oauth_token",$access_token['oauth_token']);
				$auth_file->addChild("oauth_token_secret",$access_token['oauth_token_secret']);
				$xml = $auth_file->asXML();
				$auth_xml = fopen("auth.xml", "w");
				fwrite($auth_xml, $xml);
				fclose($auth_xml);
		
				// Show completion
				echo "Tweeting with Alfred setup is now complete. Start tweeting!";
			
			} //end else (no timeout)
		
		} //end if for setup
	
		// Set the pin number
		else if ($input[0] == "pin") {
		
			// Write the pin number to pin.txt
			$result = system("echo $input[1] > pin.txt");
			echo "Pin number set. Please wait while authorization is completed.";
		
		} //end else if for pin
	
		else if ($input[0] == "dm") {
		
			$username = $input[1];
			unset($input[0]);
			unset($input[1]);
			$message = implode(" ", $input);
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) { echo "Unable to send dm. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//send dm
				$res = $tweet->post('direct_messages/new', array('screen_name' => $username, 'wrap_links' => true, 'text' => $message));
			
				//display result/dm
				if (isset($res->error)) { echo $res->error; }
				else { echo "Direct message to $username successfully sent!"; }
			
			} // end else
		
		} //end else if direct messages

		/*else if ($input[0] == "dms") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) { echo "Unable to send dm. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//send dm
				$res = $tweet->get('direct_messages', array('count'=>1));
			
				//display result/dms
				if (isset($res->error)) { echo $res->error; }
				else { 
					/*$inc=1;
					foreach($res as $dm):
						$user = $dm->sender_screen_name;
						if ($inc == count($res)) { echo "$dm->text - @$user "; }
						else { echo "$dm->text\r - @$user \r\r"; }
						$inc++;
					endforeach;
				}
			
			} // end else
		
		} //end else if get direct messages*/
	
		else if ($input[0] == "tweets") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to grab tweets. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//get recent tweets
				$res = $tweet->get('statuses/home_timeline', array('count' => 6));
			
				//display result/tweets
				if (isset($res->error)) { echo $res->error; }
				else { 
					$inc=1;
					foreach($res as $timeline):
						$user = $timeline->user->screen_name;
						if ($inc == count($res)) { echo "$timeline->text - @$user "; }
						else { echo "$timeline->text\r - @$user \r\r"; }
						$inc++;
					endforeach;
				}
			
			} // end else
		
		} //end else if tweets

		else if ($input[0] == "info") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to grab user info. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//get user info
				$res = $tweet->get('users/show', array('count' => 6, 'screen_name'=>$input[1]));
			
				//display result/user info
				if (isset($res->error)) { 
					if ($res->error == "Not found") {
						echo "Unable to find info for that user.";	
					}
					else { echo $res->error; }
				}
				else { 
					$status = $res->status->text;
					if ($res->following) {
						$following = "(following)";
					}
					else {
						$following = "";
					}
					echo "User: $res->screen_name $following\r";
					echo "Name: $res->name\r";
					echo "Location: $res->location\r";
					echo "URL: $res->url\r";
					echo "Tweets: $res->statuses_count\r\r";
					echo "Description:\r $res->description\r\r";
					echo "Last tweet:\r $status";
				}
			
			} // end else
		
		} //end else if tweets

		else if ($input[0] == "mentions") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to grab mentions. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//get mentions
				$res = $tweet->get('statuses/mentions', array('count' => 6));
			
				//display result/mentions
				if (isset($res->error)) { echo $res->error; }
				else { 
					$inc=1;
					foreach($res as $mention):
						$user = $mention->user->screen_name;
						if ($inc == count($res)) { echo "$mention->text - @$user "; }
						else { echo "$mention->text\r - @$user \r\r"; }
						$inc++;
					endforeach;
				}
			
			} // end else
		
		}
	
		else if ($input[0] == "follow") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to follow user. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//follow user
				$res = $tweet->post('friendships/create', array('screen_name' => $input[1]));
			
				//display result
				if (isset($res->error)) { echo $res->error; }
				else { echo "Now following ".$input[1]; }
			
			} // end else
		
		}
	
		else if ($input[0] == "unfollow") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to unfollow user. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//unfollow user
				$res = $tweet->post('friendships/destroy', array('screen_name' => $input[1]));
			
				//display result
				if (isset($res->error)) { echo $res->error; }
				else { echo "Now following ".$input[1]; }
			
			} // end else
		
		}
	
		else if ($input[0] == "block") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to block user. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//block user
				$res = $tweet->post('blocks/create', array('screen_name' => $input[1]));
			
				//display result
				if (isset($res->error)) { echo $res->error; }
				else { echo "Successfully blocked ".$input[1]; }
			
			} // end else
		
		}
	
		else if ($input[0] == "unblock") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to unblock user. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//unblock user
				$res = $tweet->post('blocks/destroy', array('screen_name' => $input[1]));
			
				//display result
				if (isset($res->error)) { echo $res->error; }
				else { echo "No longer blocking ".$input[1]; }
			
			} // end else
		
		}

		else if ($input[0] == "search") {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) {  echo "Unable to perform search. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//get search results
				$res = $tweet->get('search', array('q' => $input[1], 'result_type'=>'recent', 'rpp'=>5));
			
				//display result(s)
				if (isset($res->error)) { echo $res->error; }
				else { 
					$inc=1;
					foreach($res->results as $result):
						$user = $result->from_user;
						if ($inc == count($res->results)) { echo "$result->text - @$user "; }
						else { echo "$result->text\r - @$user \r\r"; }
						$inc++;
					endforeach;
				}
			
			} // end else
		
		}
	
		// assuming setup is complete
		else {
		
			$auth = check_auth();
		
			// If oauth values aren't set, assumed that the user hasn't run setup yet.
			if ($auth == false) { echo "Unable to post tweet. You must run setup and authenticate first."; }
			else {
		
				// create a new instance
				$tweet = new TwitterOAuth($appkey1, $appkey2, $auth['oAuthKey'], $auth['oAuthSecret']);
		
				//send a tweet
				$res = $tweet->post('statuses/update', array('status' => $argv[1], 'wrap_links' => true));
			
				if (isset($res->error)) { echo $res->error; }
				else { echo "Tweet successfully posted!"; }
			
			} // end else
		
		} //end else (searching for commands)
	}
	
	
	function check_auth() {
		// Read auth.xml for credentials
		$auth = simplexml_load_file("auth.xml");
		
		// Grab oauth token values
		$ret['oAuthKey'] = "$auth->oauth_token";
		$ret['oAuthSecret'] = "$auth->oauth_token_secret";
		
		// If oauth values aren't set, assumed that the user hasn't run setup yet.
		if ($ret['oAuthKey'] == "" || $ret['oAuthSecret'] == "") {  return false; }
		else { return $ret; }
	}

?>