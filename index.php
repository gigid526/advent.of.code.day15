<?php
function stamp($input) {
    echo trim(array_reduce($input, function ($carry, $row) { return $carry . PHP_EOL . implode('', $row); }, '')) . PHP_EOL;
}
function readingOrderSortFn($a, $b) {
    return $a[0] - $b[0] === 0 ? ($a[1] - $b[1]) : $a[0] - $b[0];
}
function dfs($point, $path, $destination, &$paths, &$visited, &$shortestPathLength, &$input) {
    if (isset($visited[$point[0]][$point[1]]) && $visited[$point[0]][$point[1]] === 1) {
        return;
    }
    if (count($path) > $shortestPathLength) {
        return;
    }
    if ($point[0] === $destination[0] && $point[1] === $destination[1]) {
        if (count($path) < $shortestPathLength) {
            $shortestPathLength = count($path);
            $paths = [$path];
        } else if (count($path) === $shortestPathLength) {
            $paths[] = $path;
        }
        return;
    }
    $visited[$point[0]][$point[1]] = 1;
    foreach ([[-1, 0], [0, -1], [0, 1], [1, 0]] as $transition) {
        $y = $point[0] + $transition[0];
        $x = $point[1] + $transition[1];
        if ($y >= 0 && $y < count($input) && $x >= 0 && $x < count($input[$y]) && $input[$y][$x] === '.') {
            dfs([$y, $x], array_merge($path, [[$y, $x]]), $destination, $paths, $visited, $shortestPathLength, $input);
        }
    }
    $visited[$point[0]][$point[1]] = 0;
}
function findShortestPath($from, $to, &$input) {
    $visited = [];
    $paths = [];
    $shortestPathLength = PHP_INT_MAX;
    dfs($from, [], $to, $paths, $visited, $shortestPathLength, $input);
    foreach ($paths as $path) {
        if (count($path) === $shortestPathLength) {
            return $path;
        }
    }
    return null;
}


$flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES;
$input = array_map(function ($line) { return str_split($line); }, file(__DIR__ . '/input9.txt', $flags));
$units = [];
for ($y = 0; $y < count($input); ++$y) {
    for ($x = 0; $x < count($input[$y]); ++$x) {
        if (in_array($input[$y][$x], ['G', 'E'])) {
            $units[] = [$y, $x, $input[$y][$x], 3, 200, 1];
        }
    }
}

stamp($input);

$round = 0;
while (true) {
//for ($t = 0; $t < 24; ++$t) {
    echo 'The round ' . $round . PHP_EOL;
    for ($i = 0; $i < count($units); ++$i) {
        if ($units[$i][5] === 0) { // skips if a unit is marked as dead
            continue;
        }
        $unit = $units[$i]; // abbr.
        //echo 'The unit ' . $unit[0] . ', ' . $unit[1] . ' turn' . PHP_EOL;
        // finds all targets
        $targets = [];
        foreach ([[-1, 0], [0, -1], [0, 1], [1, 0]] as $transition) {
            $y = $unit[0] + $transition[0];
            $x = $unit[1] + $transition[1];
            if (
                $y >= 0 && $y < count($input)
                && $x >= 0 && $x < count($input[$y])
                && $input[$y][$x] === ($unit[2] === 'E' ? 'G' : 'E')
            ) {
                $targets[] = [$y, $x];
            }
        }
        // if no targets then move
        if (count($targets) === 0) {
            $destinations = [];
            foreach ($units as $anotherUnit) {
                if ($unit[2] !== $anotherUnit[2] && $anotherUnit[5] === 1) { //  checks the species
                    foreach ([[-1, 0], [0, -1], [0, 1], [1, 0]] as $transition) {
                        $y = $anotherUnit[0] + $transition[0];
                        $x = $anotherUnit[1] + $transition[1];
                        if (
                            $y >= 0 && $y < count($input)
                            && $x >= 0 && $x < count($input[$y])
                            && $input[$y][$x] === '.'
                        ) {
                            $destinations[] = [$y, $x];
                        }
                    }
                }
            }
            usort($destinations, 'readingOrderSortFn'); // sorts destinations
            $nearestDestination = null;
            $nearestPathLength = null;
            foreach ($destinations as $destination) {
                $path = findShortestPath([$unit[0], $unit[1]], $destination, $input);
                // finds the nearest path
                if ($path !== null && (is_null($nearestPathLength) || count($path) < $nearestPathLength)) {
                    $nearestDestination = [$destination[0], $destination[1], $path];
                    $nearestPathLength = count($path);
                }
            }
            // moves if the nearest destination is found
            if ($nearestDestination !== null) {
                $input[$unit[0]][$unit[1]] = '.'; // movement
                $input[$nearestDestination[2][0][0]][$nearestDestination[2][0][1]] = $unit[2]; // movement
                $units[$i][0] = $nearestDestination[2][0][0];
                $units[$i][1] = $nearestDestination[2][0][1];
                foreach ([[-1, 0], [0, -1], [0, 1], [1, 0]] as $transition) {
                    $y = $units[$i][0] + $transition[0];
                    $x = $units[$i][1] + $transition[1];
                    if (
                        $y >= 0 && $y < count($input)
                        && $x >= 0 && $x < count($input[$y])
                        && $input[$y][$x] === ($unit[2] === 'E' ? 'G' : 'E')
                    ) {
                        $targets[] = [$y, $x];
                    }
                }
                //var_dump($nearestDestination);
                //stamp($input);
            }
        }
        // attacks if the unit has targets
        if (count($targets) > 0) {
            $bestTarget = null;
            foreach ($targets as $target) {
                for ($j = 0; $j < count($units); ++$j) {
                    if (
                        $units[$j][0] === $target[0] && $units[$j][1] === $target[1] &&  $units[$j][5] === 1
                        && (is_null($bestTarget) || $units[$bestTarget][4] > $units[$j][4])
                    ) {
                        $bestTarget = $j;
                    }
                }
            }
            //echo 'The unit ' . $units[$i][0] . ', ' . $units[$i][1] . ' has found the best target: ' . 
            //    $units[$bestTarget][0] . ', ' . $units[$bestTarget][1] . PHP_EOL;
            $units[$bestTarget][4] -= $unit[3];
            //echo 'The unit ' . $units[$bestTarget][0] . ', ' . $units[$bestTarget][1] . ' has received ' . $unit[3]
            //    . ' DMG, it has ' . $units[$bestTarget][4] . ' HP now' . PHP_EOL;
            if ($units[$bestTarget][4] < 1) {
                $input[$units[$bestTarget][0]][$units[$bestTarget][1]] = '.';
                $units[$bestTarget][5] = 0; // marks as dead
            }
        }
    }
    usort($units, 'readingOrderSortFn');
    stamp($input);
    $remaining = ['E' => 0, 'G' => 0];
    for ($i = 0; $i < count($units); ++$i) {
        if ($units[$i][5] === 0) {
            continue;
        }
        echo '(' . $units[$i][0] . ', ' . $units[$i][1] . ', ' . $units[$i][4] . ' HP) ';
        $remaining[$units[$i][2]] += $units[$i][4];
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
