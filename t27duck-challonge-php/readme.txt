<?php
// Include the class on your page somewhere
include('challonge.class.php');

// Create a new instance of the API wrapper. Pass your API key into the constructor
// You can view/generate your API key on the 'Password / API Key' tab of your account settings page.
$c = new ChallongeAPI(YOUR_API_KEY_HERE);


/*
  For whatever reason (example: developing locally) if you get a SSL validation error from CURL,
  you can set the verify_ssl attribute of the class to false. It is highly recommended that you
  **NOT** do this on a production server.
  $c->verify_ssl = false;

  ------------------------------------------------------------------------------------------------------------

  The exmaples below are divided into the same 3 categories (Tournaments, Participants, Matches)
  as they are presented in the API documentation (http://challonge.com/api).
  
  Each example contains two methods:
  The first one uses the makeCall() method used to make direct API calls. This method offers the
  most control, but is the most complex method in this API wrapper.
  The second method in each example is the equivalent "alias" method that builds the corresponding
  makeCall() method for you. All you need to do is pass in the correct parameters.
  
  The alias methods are eaiser to use and are more human-readable. Doesn't matter if you use 
  makeCall(), alias methods, or a combination of the two in your application as they all return the
  same results.
  
  ------------------------------------------------------------------------------------------------------------
  
  List of available alias methods:
  
  getTournaments($params)
  getTournament($tournament_id, $params)
  createTournament($params)
  updateTournament($tournament_id, $params)
  deleteTournament($tournament_id)
  publishTournament($tournament_id, $params)
  startTournament($tournament_id, $params)
  resetTournament($tournament_id, $params)
  
  getParticipants($tournament_id)
  getParticipant($tournament_id, $participant_id, $params)
  createParticipant($tournament_id, $params)
  updateParticipant($tournament_id, $participant_id, $params)
  deleteParticipant($tournament_id, $participant_id)
  randomizeParticipants($tournament_id)
  
  getMatches($tournament_id, $params)
  getMatch($tournament_id, $match_id)
  updateMatch($tournament_id, $match_id, $params)
  
  ------------------------------------------------------------------------------------------------------------
  
  All methods that result in API calls to the server return either a SimpleXML object containing the 
  results of the call or false.
  
  If false is returned, look at the public errors array for errors.
  Example:
  $t = $c->getTournaments();
  if ($t == false) {
    foreach ($c->errors as $error) {
      echo $error."\n"; // Output the error message
    }
  }
  
  ------------------------------------------------------------------------------------------------------------
  
  The public instance variable status_code holds the status code returned from the last API call
  Example:
  $t = $c->getTournaments();
  echo $c->status_code; // If all goes well, this will be 200
  
  ------------------------------------------------------------------------------------------------------------
  
  The public instance variable result holds the returned value of the last API call. Useful for debuging if
  the method you call returns false and you want to check if any data is actually returned from the server.
  
  Examples:
  $t = $c->getTournaments();
  $last_result = $c->result; // In this case, $last_result == $t
  
  $t = $c->createTournament( array("tournament[name]" => "My Tourney") ); 
  // We didn't provide enough data, so $t is false
  // We could look through the $c->errors array to see what we're missing, or we could look at the actual
  // return value from the server
  print_r( $c->result );
  
  ------------------------------------------------------------------------------------------------------------
  
  NOTES:
  
  In the below examples, when you see $tournament_id, it can ether be a number or the URL of the tournament.
  See the official documentation for more details.
    
  In the below examples, $params is an associative array
  
  ------------------------------------------------------------------------------------------------------------
  
  makeCall() USAGE:
  makeCall($path="", $params=array(), $method);
  
  $path - String: URL path of the call. Mirrors the API documentation's URLs after the https://challonge.com/api/
    Examples:
      "tournaments/$tournament_id"
      "tournaments/$tournament_id/matches"
  $params - Associative Array: Key-value pairs of parameters to sent to the server
  $method - String: HTTP methods. get (default), put, post, or delete
*/


// TOURNAMENT EXAMPLES
// ************************
// Get all tournaments you created
// http://challonge.com/api/tournaments
$tournaments = $c->makeCall('tournaments');
$tournaments = $c->getTournaments();

// Get completed tournamnets you created made after 2010-12-09
// http://challonge.com/api/tournaments
$params = array( 
  "state" => "ended",
  "created_after" => "2010-12-09"
  );
$tournaments = $c->makeCall('tournaments', $params);
$tournaments = $c->getTournaments($params);

// Get a tournament
// http://challonge.com/api/tournaments/show/:tournament
$tournament_id = 12345;
$params = array("include_matches " => 0);
$tournament = $c->makeCall("tournaments/$tournament_id", $params, "get");
$tournament = $c->getTournament($tournament_id, $params);

// Create a tournament
// http://challonge.com/api/tournaments/create
$params = array(
  "tournament[name]" => "My Tourney",
  "tournament[tournament_type]" => "single elimination",
  "tournament[url]" => "my_toruney",
  "tournament[description]" => "Challonge is <strong>AWESOME</strong>"
  );
$tournament = $c->makeCall("tournaments", $params, "post");
$tournament = $c->createTournament($params);


// Update a tournament
// http://challonge.com/api/tournaments/update/:tournament
$tournament_id = 12345;
$params = array(
  "tournament[description]" => "Challonge is still <strong>AWESOME</strong>"
  );
$tournament = $c->makeCall("tournaments/$tournament_id", $params, "put");
$tournament = $c->updateTournament($tournament_id, $params);

// Delete a tournament
// http://challonge.com/api/tournaments/destroy/:tournament
$tournament_id = 12345;
$tournament = $c->makeCall("tournaments/$tournament_id", array(), "delete");
$tournament = $c->deleteTournament($tournament_id);

// Publish a tournament
// http://challonge.com/api/tournaments/publish/:tournament
$tournament_id = 12345;
$params = array();
$tournament = $c->makeCall("tournaments/publish/$tournament_id", $params, "post");
$tournament = $c->publishTournament($tournament_id, $params);

// Start a tournament
// http://challonge.com/api/tournaments/start/:tournament
$tournament_id = 12345;
$params = array();
$tournament = $c->makeCall("tournaments/start/$tournament_id", $params, "post");
$tournament = $c->startTournament($tournament_id, $params);

// Reset a tournament
// http://challonge.com/api/tournaments/reset/:tournament
$tournament_id = 12345;
$params = array();
$tournament = $c->makeCall("tournaments/reset/$tournament_id", $params, "post");
$tournament = $c->resetTournament($tournament_id, $params);



// PARTICIPANTS EXAMPLES
// ************************
// Get all participants
// http://challonge.com/api/tournaments/:tournament/participants
$tournament_id = 12345;
$participants = $c->makeCall("tournaments/$tournament_id/participants");
$participants = $c->getParticipants($tournament_id);

// Get a participant in a tournament
// http://challonge.com/api/tournaments/:tournament/participants/show/:participant_id
$tournament_id = 12345;
$participant_id = 54321;
$params = array("inlcude_matches" => "0");
$participant = $c->makeCall("tournaments/$tournament_id/participants/$participant_id", $params);
$participant = $c->getParticipant($tournament_id, $participant_id, $params);

// Add a participant in a tournament
// http://challonge.com/api/tournaments/:tournament/participants/create
$tournament_id = 12345;
$params = array(
  "participant[name]" => "Mr Duck",
  "participant[seed]" => "2"
  );
$participant = $c->makeCall("tournaments/$tournament_id/participants", $params, "post");
$participant = $c->createParticipant($tournament_id, $params);

// Update a participant in a tournament
// http://challonge.com/api/tournaments/:tournament/participants/create
$tournament_id = 12345;
$participant_id = 54321;
$params = array(
  "participant[name]" => "Mr Duck",
  "participant[seed]" => "2"
  );
$participant = $c->makeCall("tournaments/$tournament_id/participants/$participant_id", $params, "put");
$participant = $c->updateParticipant($tournament_id, $participant_id, $params);

// Delete a participant in a tournament
// http://challonge.com/api/tournaments/:tournament/participants/destroy/:participant_id
$tournament_id = 12345;
$participant_id = 54321;
$participant = $c->makeCall("tournaments/$tournament_id/participants/$participant_id", array(), "delete");
$participant = $c->deleteParticipant($tournament_id, $participant_id);

// Randomize a participants in a tournament
// http://challonge.com/api/tournaments/:tournament/participants/randomize
$tournament_id = 12345;
$participants = $c->makeCall("tournaments/$tournament_id/participants/randomize", array(), "post");
$participants = $c->randomizeParticipants($tournament_id);


// PARTICIPANTS EXAMPLES
// ************************
// Get all matches for a tournament
// http://challonge.com/api/tournaments/:tournament/matches
$tournament_id = 12345;
$parmas = array();
$matches = $c->makeCall("tournaments/$tournament_id/matches", $params);
$matches = $c->getMatches($tournament_id, $params);

// Get one matche for a tournament
// http://challonge.com/api/tournaments/:tournament/matches/:match_id
$tournament_id = 12345;
$match_id = 7890;
$match = $c->makeCall("tournaments/$tournament_id/matches/$match_id");
$match = $c->getMatch($tournament_id, $match_id);

// Update a match and submit scores
// http://challonge.com/api/tournaments/:tournament/matches/update/:match_id
$tournament_id = 12345;
$match_id = 7890;
$params = array(
  "match[scores_csv]" => "3-1",
  "match[winner_id]" => "9870"
  );
$match = $c->makeCall("tournaments/$tournament_id/matches/$match_id", $params, "put");
$match = $c->updateMatch($tournament_id, $match_id, $params);

?>