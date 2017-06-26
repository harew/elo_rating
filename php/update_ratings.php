<?php

function sortStandings_score($a,$b){
	return $a[1]<$b[1];
	# code...
}
function sortStandings_seed($a,$b){
	return $a[3]<$b[3];
	# code...
}
function getRound(){
	global $round_number,$round,$users;
	/*
		open csv and read get user_id and score of user 
	*/
	if (($handle = fopen("round_".$round_number.".csv", "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	       	$standing = array($data[1],$data[7]);
	       	array_push($round['standings'],$standing);
	    }
	    fclose($handle);
	}
}
function expected($a,$b){
	return 1/(1+pow(10,(($b-$a)/400)));
}
function elo($old_rating,$expected_rank,$actual_rank){
	$k = 32; // can change for divisions
	return $old_rating + $k * ($expected_rank - $actual_rank);
}

function processRound(){
	global $round_number,$round,$users;
	/*
		process round for new ratings:
		algorithms-------
		1)sort standings
		2)for every standing check if user not exsists and add user_id key to users array with rating=100 and rounds=0 (first round of user *****)
		3)calculate seed by taking sumation of probablities using expected function
		4)add round number to users data with keys previous_rating and actual_rank


	*/
	getRound();
	$round['total_participants'] = sizeof($round['standings']);
	usort($round['standings'],'sortStandings_score');
	for($i=0;$i<sizeof($round['standings']);$i++){

		$user_id = $round['standings'][$i][0];
		if(!array_key_exists($user_id,$users))
			$users[$user_id] = array('rating' => 1000,'user_rounds'=>0);		
		array_push($round['standings'][$i],$i+1);	
		$users[$user_id][$round_number] = array('previous_rating' =>$users[$user_id]['rating'],'actual_rank'=>$i+1);
	}

	for($i=0;$i<sizeof($round['standings']);$i++){
		$user_id = $round['standings'][$i][0];
		$seed = 1;
		for($j=0;$j<sizeof($round['standings']);$j++){
			$opponent_id = $round['standings'][$j][0];
			if($user_id!=$opponent_id)
				$seed = $seed + expected($users[$user_id]['rating'],$users[$opponent_id]['rating']);
		}
		$users[$user_id][$round_number]['seed'] = $seed;
		array_push($round['standings'][$i],$seed);	
	}
	usort($round['standings'],'sortStandings_seed');
	for($i=0;$i<sizeof($round['standings']);$i++){
		$user_id = $round['standings'][$i][0];
		$expected_rank = $i+1;
		if($users[$user_id]['user_rounds']==0)
			$expected_rank = ($round['total_participants']/2 + 1);
		array_push($round['standings'][$i],$expected_rank);
		$users[$user_id][$round_number]['expected_rank'] = $expected_rank;
		$users[$user_id][$round_number]['new_rating'] = elo($users[$user_id][$round_number]['previous_rating'],$users[$user_id][$round_number]['expected_rank'],$users[$user_id][$round_number]['actual_rank']);	
		$users[$user_id]['rating'] = $users[$user_id][$round_number]['new_rating'];
		$users[$user_id]['user_rounds'] = $users[$user_id]['user_rounds'] + 1;
	}
	echo json_encode($users);
	file_put_contents('users.json',json_encode($users));
}

function printStandings(){
	global $round_number,$round,$users;
	for ($i=0; $i <sizeof($round['standings']); $i++) {
		array_push($round['standings'][$i],$i+1);
		print_r($round['standings'][$i]);
		echo "<br>";
	}
}

function getUsers(){
	return json_decode(file_get_contents('users.json'), true);
}

$round = array('total_participants' => 0, 'standings' => array());
$users = getUsers();
$round_number = 1;
processRound(1);
?>