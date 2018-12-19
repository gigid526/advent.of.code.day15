<?php
$flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
$input = array_map(function ($line) { return str_split($line); }, file(__DIR__ . '/inputs/inputPuzzle.txt', $flags));
$units = [];
$points = [];
for ($y = 0; $y < count($input); ++$y) {
    for ($x = 0; $x < count($input[$y]); ++$x) {
        if (in_array($input[$y][$x], ['G', 'E', '.'])) {
            $i = count($input[0]) * $y + $x;
            $points[$i] = [$y, $x, []];
            foreach ([[-1, 0], [0, -1], [0, 1], [1, 0]] as $transition) {
                $ty = $y + $transition[0];
                $tx = $x + $transition[1];
                if ($ty >= 0 && $ty < count($input) && $tx >= 0 && $tx < count($input[$ty]) && in_array($input[$ty][$tx], ['G', 'E', '.'])) {
                    array_push($points[$i][2], count($input[0]) * $ty + $tx);
                }
            }
            if (in_array($input[$y][$x], ['G', 'E'])) {
                array_push($units, [$i , $input[$y][$x], 200]); // [point, species, HP]
            }
        }
    }
}
function sortUnits() { global $units; usort($units, function ($a, $b) { return $a[0] - $b[0]; }); }
function stamp($input) {
    echo trim(array_reduce($input, function ($carry, $row) { return $carry . PHP_EOL . implode('', $row); }, '')) . PHP_EOL;
}
function bfs($source, $destination) {
    global $points, $units, $input;
    $queue = [];
    $length = count($input) * count($input[0]);
    $visited = array_fill(0, $length, false);
    $dist = array_fill(0, $length, PHP_INT_MAX);
    $pred = array_fill(0, $length, -1);
    $visited[$source] = true;
    foreach ($units as $unit) {
        if ($unit[2] > 0) {
            $visited[$unit[0]] = true;
        }
    }
    $dist[$source] = 0;
    array_push($queue, $source);
    while (count($queue)) {
        $u = array_shift($queue);
        foreach ($points[$u][2] as $adj) {
            if ($visited[$adj] === false) {
                $visited[$adj] = true;
                $dist[$adj] = $dist[$u] + 1;
                $pred[$adj] = $u;
                array_push($queue, $adj);
                if ($adj === $destination) {
                    $curr = $adj;
                    $path = [];
                    while ($curr !== $source) {
                        array_unshift($path, $curr);
                        $curr = $pred[$curr];
                    }
                    return $path;
                }
            }
        }
    }
    return null;
}
sortUnits();

stamp($input);
$round = 0;
while (true) {
    echo 'The round ' . $round . PHP_EOL;
    for ($u = 0; $u < count($units); ++$u) {
        if ($units[$u][2] <= 0) {
            continue;
        }
        $unit = $units[$u];
        $targets = [];
        foreach ($points[$unit[0]][2] as $adj) {
            if ($input[$points[$adj][0]][$points[$adj][1]] === ($unit[1] === 'E' ? 'G' : 'E')) {
                array_push($targets, $adj);
            }
        }
        if (count($targets) === 0) {
            $destinations = [];
            foreach ($units as $anotherUnit) {
                if ($unit[0] !== $anotherUnit[0] && $unit[1] !== $anotherUnit[1] && $anotherUnit[2] > 0) { //  checks the species
                    foreach ($points[$anotherUnit[0]][2] as $adj) {
                        if ($input[$points[$adj][0]][$points[$adj][1]] === '.') {
                            array_push($destinations, $adj);
                        }
                    }
                }
            }
            sort($destinations);
            $nearestDestination = null;
            $nearestDestinationDistance = null;
            foreach ($destinations as $destination) {
                $path = bfs($unit[0], $destination);
                if ($path !== null && (is_null($nearestDestinationDistance) || count($path) < $nearestDestinationDistance)) {
                    $nearestDestination = $path;
                    $nearestDestinationDistance = count($path);
                }
            }
            if ($nearestDestination !== null) {
                $input[$points[$unit[0]][0]][$points[$unit[0]][1]] = '.'; // movement
                $step = $nearestDestination[0];
                $input[$points[$step][0]][$points[$step][1]] = $unit[1];
                $units[$u][0] = $step;
                $unit[0] = $step;
                foreach ($points[$unit[0]][2] as $adj) {
                    if ($input[$points[$adj][0]][$points[$adj][1]] === ($unit[1] === 'E' ? 'G' : 'E')) {
                        array_push($targets, $adj);
                    }
                }
            }
        }
        if (count($targets) > 0) {
            $bestTarget = null;
            foreach ($targets as $target) {
                for ($j = 0; $j < count($units); ++$j) {
                    if ($units[$j][0] === $target &&  $units[$j][2] > 0 && (is_null($bestTarget) || $units[$bestTarget][2] > $units[$j][2])) {
                        $bestTarget = $j;
                    }
                }
            }
            $units[$bestTarget][2] -= 3;
            if ($units[$bestTarget][2] < 1) {
                $input[$points[$units[$bestTarget][0]][0]][$points[$units[$bestTarget][0]][1]] = '.';
            }
        }
    }
    sortUnits();
    stamp($input);
    $remaining = ['E' => 0, 'G' => 0];
    for ($i = 0; $i < count($units); ++$i) {
        if ($units[$i][2] < 1) {
            continue;
        }
        echo '(' . implode(', ', $units[$i]) . ' HP) ';
        $remaining[$units[$i][1]] += $units[$i][2];
    }
    echo PHP_EOL;
    if ($remaining['E'] === 0 || $remaining['G'] === 0) {
        echo 'The result:' . PHP_EOL;
        foreach ($remaining as $species => $points) {
            echo $species . ', points: ' . $points . ', outcome: ' . ($points * $round) . PHP_EOL; 
        }
        break;
    }
    $round++;
}
