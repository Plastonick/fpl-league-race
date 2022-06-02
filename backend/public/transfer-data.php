<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

include __DIR__ . '/../vendor/autoload.php';

$playerId = getPlayerId();

function getPlayerId(): int
{
    preg_match('/transfers\/(\d+)/', $_SERVER['REQUEST_URI'], $matches);

    if (isset($matches[1])) {
        return (int) $matches[1];
    } else {
        $message = <<<HELP
Please enter your player id in the URL, if your player ID were 123 you'd enter: http://leagueracefpl.info/transfers/123
HELP;

        die($message);
    }
}

class PlayerSprint
{
    public function __construct(
        public int $id,
        public int $type,
        public int $start,
        public int $end,
        public string $name,
        public int $sort,
        public int $team
    ) {
    }
}

class Row
{
    /** @var PlayerSprint[] */
    private array $sprints = [];

    public function addSprint(PlayerSprint $sprint): void
    {
        $this->sprints[$sprint->id] = $sprint;
    }

    public function getSprints(): array
    {
        return $this->sprints;
    }

    public function getSprintById(int $id): ?PlayerSprint
    {
        return $this->sprints[$id] ?? null;
    }
}

$filesystemAdapter = new Local('/var/tmp/cache');
$filesystem = new Filesystem($filesystemAdapter);
$pool = new FilesystemCachePool($filesystem);

$key = 'bootstrap';
if (!$bootstrap = $pool->get($key)) {
    $pool->set($key, json_decode(file_get_contents('https://fantasy.premierleague.com/api/bootstrap-static/'), true));
    $bootstrap = $pool->get($key);
}

$elements = $bootstrap['elements'];

$elementsById = [];

foreach ($elements as $element) {
    $elementsById[$element['id']] = [$element['id'], $element['web_name'], $element['team'], $element['element_type']];
}


//id
//first_name
//second_name

/**
 * @param int $playerId
 * @param array $elementsById
 *
 * @return Row[]
 */
function getPlayersData(int $playerId, array $elementsById, CacheInterface $pool): array
{
    /** @var Row[] $rows */
    $rows = [];

    foreach (range(1, 38) as $gameWeek) {
        $key = "event.picks.{$playerId}.{$gameWeek}";
        if (!$pool->has($key)) {
            $pool->set(
                $key,
                json_decode(
                    file_get_contents(
                        "https://fantasy.premierleague.com/api/entry/{$playerId}/event/{$gameWeek}/picks/"
                    ),
                    true
                )
            );
        }
        $picks = $pool->get($key);

        $players = $picks['picks'];

        $row = new Row();
        $positions = [
            1 => [1, 2],
            2 => [3, 4, 5, 6, 7],
            3 => [8, 9, 10, 11, 12],
            4 => [13, 14, 15],
        ];
        if ($gameWeek > 1) {
            $previousRow = $rows[$gameWeek - 1];

            foreach ($players as $i => $player) {
                $id = $player['element'];
                [$id] = $elementsById[$id];

                // check if the player was already in the previous game week
                if ($sprint = $previousRow->getSprintById($id)) {
                    $sprint->end = $gameWeek;

                    $key = array_search($sprint->sort, $positions[$sprint->type]);
                    unset($positions[$sprint->type][$key]);
                    unset($players[$i]);
                    $row->addSprint($sprint);
                }
            }
        }

        foreach ($players as $player) {
            $id = $player['element'];

            [$id, $name, $team, $type] = $elementsById[$id];
            $sort = current($positions[$type]);
            next($positions[$type]);

            $row->addSprint(new PlayerSprint($id, $type, $gameWeek, $gameWeek, $name, $sort, $team));
        }

        $rows[$gameWeek] = $row;
    }

    return $rows;
}

$rows = getPlayersData($playerId, $elementsById, $pool);

echo <<<HTML
<div class="container">
HTML;

function getTeamClass(int $team): string
{
    return match ($team) {
        1 => 'arsenal',
        2 => 'aston-villa',
        3 => 'brentford',
        4 => 'brighton',
        5 => 'burnley',
        6 => 'chelsea',
        7 => 'crystal-palace',
        8 => 'everton',
        9 => 'leicester',
        10 => 'leeds',
        11 => 'liverpool',
        12 => 'man-city',
        13 => 'man-utd',
        14 => 'newcastle',
        15 => 'norwich',
        16 => 'southampton',
        17 => 'spurs',
        18 => 'watford',
        19 => 'west-ham',
        20 => 'wolves',
        default => ['#66AA00', '#0077bb']
    };
}

$width = 5.73;
$rowHeight = 2.35;

$w2 = 2 * $width;
$w3 = 3 * $width;
$w5 = 5 * $width;

$l1 = $width + 1;
$l2 = $l1 + 1 + $w2;
$l3 = $l2 + 1 + $w5;
$l4 = $l3 + 1 + $w5;

echo <<<HTML
<div style="height: {$rowHeight}%; width: {$w2}%; left: {$l1}%; top: 4%" class="header element">
Goalkeepers
</div>
<div style="height: {$rowHeight}%; width: {$w5}%; left: {$l2}%; top: 4%" class="header element">
Defenders
</div>
<div style="height: {$rowHeight}%; width: {$w5}%; left: {$l3}%; top: 4%" class="header element">
Midfields
</div>
<div style="height: {$rowHeight}%; width: {$w3}%; left: {$l4}%; top: 4%" class="header element">
Forwards
</div>
HTML;


foreach ($rows as $gameWeek => $row) {
    $top = 5 + ($rowHeight * $gameWeek);
    $left = $width - 2;

    echo <<<HTML
<div style="height: {$rowHeight}%; top: {$top}%; left: {$left}%" class="row element">{$gameWeek}</div>
HTML;


    foreach ($row->getSprints() as $sprint) {
        if ($sprint->start !== $gameWeek) {
            continue;
        }

        $height = $rowHeight * ($sprint->end - $sprint->start + 1);
        $left = ($width * $sprint->sort) + $sprint->type;

        $team = getTeamClass($sprint->team);

         echo <<<HTML
<div style="top: {$top}%; left: {$left}%; height: {$height}%; width: ${width}%" class="{$team} sprint element">
{$sprint->name}
</div>
HTML;
    }
}

echo <<<HTML
</div>
<style>
.container {
  position: relative;
  height: 100%;
  width: 100%;
}

.element {
  position: absolute;
  overflow: hidden; 
  font-size: 10px;
  display: flex;
  border: 0.5px solid #aaa; 
  justify-content: center;
  align-items: center;
  text-align: center;
}

.row {
  color: #fff;
  background-color: #000;
  width: 2%;
}

.header {
  color: #fff;
  background-color: #000;
}

.sprint {
}

.arsenal {
  color: #9c824a; 
  background-color: #db0007;
}

.aston-villa {
  color: #670E36; 
  background-color: #95bfe5;
}

.brentford {
  color: #fff; 
  background-color: #e30613;
}

.brighton {
  color: #fff; 
  background-color: #005daa;
}

.burnley {
  color: #6c1d45; 
  background-color: #97d9f6;
}

.chelsea {
  color: #fff; 
  background-color: #034694;
}

.crystal-palace {
  color: #a7a5a6; 
  background-color: #1b458f;
}

.everton {
  color: #fff; 
  background-color: #274488;
}

.leicester {
  color: #fff; 
  background-color: #0053a0;
}

.leeds {
  color: #FFCD00; 
  background-color: #1D428A;
}

.liverpool {
  color: #fef667; 
  background-color: #d00027;
}

.man-city {
  color: #fff; 
  background-color: #97c1e7;
}

.man-utd {
  color: #ffe500; 
  background-color: #da020e;
}

.newcastle {
  color: #fff; 
  background-color: #000;
}

.norwich {
  color: #fff200; 
  background-color: #00a650;
}

.southampton {
  color: #fff; 
  background-color: #ed1a3b;
}

.spurs {
  color: #132257; 
  background-color: #fff;
}

.watford {
  color: #000; 
  background-color: #fbee23;
}

.west-ham {
  color: #7c2c3b; 
  background-color: #2dafe5;
}

.wolves {
  color: #000; 
  background-color: #fdb913;
}
</style>
HTML;
