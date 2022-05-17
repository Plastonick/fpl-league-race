<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

require __DIR__ . '/../vendor/autoload.php';

$leagueId = $_GET['leagueId'] ?? 999;

$filesystemAdapter = new Local('/var/tmp/cache');
$filesystem = new Filesystem($filesystemAdapter);
$pool = new FilesystemCachePool($filesystem);

$eventMapKey = 'eventdatemap';
$eventDateMap = $pool->get($eventMapKey);

if (!$eventDateMap) {
    $bootstrap = file_get_contents('https://fantasy.premierleague.com/api/bootstrap-static/');
    $events = json_decode($bootstrap, true)['events'];

    $eventDateMap = [];
    foreach ($events as $event) {
        $eventDateMap[$event['id']] = substr($event['deadline_time'], 0, 10);
    }

    $pool->set($eventMapKey, $eventDateMap, 600);
}


$leagueKey = "league{$leagueId}";
$output = $pool->get($leagueKey);
if (!$output) {
    $output = getLeagueRaceData($leagueId, $eventDateMap);
    $pool->set($leagueKey, $output, 600);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json; charset=utf-8');

echo json_encode($output);

/**
 * @param $leagueId
 * @param array $eventDateMap
 *
 * @return array
 */
function getLeagueRaceData($leagueId, array $eventDateMap)
{
    $standingsData = file_get_contents("https://fantasy.premierleague.com/api/leagues-classic/{$leagueId}/standings");
    $leagueData = json_decode($standingsData, true);
    $results = $leagueData['standings']['results'];

    $output = [0 => ['Date']];
    foreach ($results as $result) {
        $id = $result['entry'];
        $name = $result['player_name'] . ' - ' . $result['entry_name'];
        $output[0][] = $name;

        $playerResults = json_decode(
            file_get_contents("https://fantasy.premierleague.com/api/entry/{$id}/history/"),
            true
        );
        $cumulativePoints = array_column($playerResults['current'], 'total_points');

        $i = 1;
        foreach ($cumulativePoints as $points) {
            $date = $eventDateMap[$i];

            if (!isset($output[$date])) {
                $output[$date][] = $date;
            }

            $output[$date][] = $points;

            $i += 1;
        }
    }

    return array_values($output);
}
