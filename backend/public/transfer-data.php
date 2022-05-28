<?php


$playerId = $_GET['playerId'] ?? 999;


// TODO remove
$playerId = 2401198;

$bootstrap = json_decode(file_get_contents('https://fantasy.premierleague.com/api/bootstrap-static/'), true);
$elements = $bootstrap['elements'];

$elementsById = [];

foreach ($elements as $element) {
    $elementsById[$element['id']] = $element['web_name'];
}


//id
//first_name
//second_name

$top = 12;

echo '<div style="left: 50px; top: 50px; height: 500px; width: 900px">';

foreach (range(1, 38) as $gameWeek) {
    $picks = json_decode(file_get_contents("https://fantasy.premierleague.com/api/entry/{$playerId}/event/{$gameWeek}/picks/"), true);

    $players = $picks['picks'];

    $left = 5;
    foreach ($players as $player) {
        $playerName = $elementsById[$player['element']];

        echo <<<HTML
<div style="position: relative; top: {$top}%; left: {$left}%; height: 2%; width: 6%; background-color: #abcabc; border: 0.5px solid #aaa; overflow: hidden; font-size: x-small">
{$playerName}
</div>
HTML;
        $left += 6;
    }

    $top += 2;

}

echo '</div>';
