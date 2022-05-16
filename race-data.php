<?php

$leagueId = $_GET['leagueId'];

$events = json_decode(file_get_contents('https://fantasy.premierleague.com/api/bootstrap-static/'), true)['events'];
$eventDateMap = [];
foreach ($events as $event) {
	$eventDateMap[$event['id']] = substr($event['deadline_time'], 0, 10);
}


$leagueData = json_decode(file_get_contents("https://fantasy.premierleague.com/api/leagues-classic/{$leagueId}/standings"), true);
$results = $leagueData['standings']['results'];

$output = [];
foreach ($results as $result) {
	$id = $result['entry'];
	$name = $result['player_name'] . ' - ' . $result['entry_name'];

	$playerResults = json_decode(file_get_contents("https://fantasy.premierleague.com/api/entry/{$id}/history/"), true);
	$cumulativePoints = array_column($playerResults['current'], 'total_points');

	$i = 1;
	foreach ($cumulativePoints as $points) {
		$date = $eventDateMap[$i];

		$output[$name][] = [
			'date' => $date,
			'name' => $points
		];

		$i += 1;
	}
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charset=utf-8');
echo json_encode($output);
