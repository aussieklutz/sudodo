<?php
$debug = false;
$rows = 9;
$cols = $rows;
$div = 3;
$totalcells = $rows * $cols;
$fullset = array();
for($i = 0; $i < $rows; $i++) array_push($fullset, '' . ($i + 1));
$sudoku_string = '';

$meta = array();

function output() {
    global $rows, $cols, $div, $totalcells, $fullset, $debug;
    global $cells;
    $newstring = '';
    for($i = 0; $i < $rows; $i++) {
        for($j = 0; $j < $cols; $j++) {
            if(count($cells[$i][$j]) == 1) {
                $newstring .= array_values($cells[$i][$j])[0];
            } else {
                $newstring .= '.';
            }
        }
    }
    return $newstring;
}

function display($populate, $override = false) {
    global $rows, $cols, $div, $totalcells, $fullset, $debug;
    global $cells, $meta;
    global $sudoku_string;
    $notfound = $totalcells;
    $error = false;
    if($GLOBALS['debug'] || $override) echo 'Display' . PHP_EOL;
    if($populate) if($GLOBALS['debug']) echo 'Populating' . PHP_EOL;
    if($populate) {
        $cells = array();
        $meta = array();
    }
    for($i = 0; $i < $rows; $i++) {
        if ($i % floor($rows/$div) == 0) if($GLOBALS['debug'] || $override) echo str_pad('', (($cols*4) + $div + 1), '-') . PHP_EOL;
        if($populate) {
            $cells[$i] = array();
            $meta[$i] = array();
        }
        for($j = 0; $j < $cols; $j++) {
            $cell_string = substr($sudoku_string, $i*$cols + $j, 1);
            if ($j % $div == 0) if($GLOBALS['debug'] || $override) echo '|';
            if($populate) {
                if($cell_string == '.') {
                    $cells[$i][$j] = array_values($fullset);
                    $meta[$i][$j] = array();
                    $meta[$i][$j]['setter'] = '';
                } else {
                    $cells[$i][$j] = array($cell_string);
                    $meta[$i][$j] = array();
                    $meta[$i][$j]['setter'] = 'p';
                }
            }
            if(count($cells[$i][$j]) == 1 || $override) {
                if($GLOBALS['debug'] || $override) echo array_values($cells[$i][$j])[0];
                $notfound--;
            } elseif (count($cells[$i][$j]) > 1 && count($cells[$i][$j]) <= $rows) {
                if($GLOBALS['debug'] || $override) echo '.';
            } else {
                if($GLOBALS['debug'] || $override) echo '!';
                $error = true;
            }
            if($GLOBALS['debug']) echo('(' . count($cells[$i][$j]) . ')');
            if($override) echo('(' . $meta[$i][$j]['setter'] . ')');
        }
        if($GLOBALS['debug'] || $override) echo '|' . PHP_EOL;
    }
    if($GLOBALS['debug'] || $override) echo str_pad('', (($rows*4) + $div + 1), '-') . PHP_EOL;
    if($debug) sleep(10);
    if($error) {
        file_put_contents('crashdump.json', json_encode($cells));
        exit(1);
    }
    return $notfound;
}

function remainder() {
    global $rows, $cols, $div, $totalcells;
    global $cells;
    $possible_values = count($cells, COUNT_RECURSIVE);
    if($GLOBALS['debug']) echo 'Possible Values: ' . $possible_values . PHP_EOL;
    return $possible_values - $totalcells;
}

function reduce() {
    global $rows, $cols, $div, $totalcells, $debug;
    global $cells, $meta;
    $reduced = 0;
    // walk the cells
    for($i = 0; $i < $rows; $i++) {
        for($j = 0; $j < $cols; $j++) {
            $possible_values = count($cells[$i][$j]);
            if($debug) if($GLOBALS['debug']) echo '(' . $i . ', ' . $j . '): ' . PHP_EOL;
            if($debug) print_r($cells[$i][$j]);
            if(count($cells[$i][$j]) == 1) {
                // echo 'propogating' . PHP_EOL;
                // reduce row adjacent values
                for($k = 0; $k < $cols; $k++) {
                    // walk columns in row, exept self, and remove options
                    if($k != $j && count($cells[$i][$k]) > 1) {
                        $init = count($cells[$i][$k]);
                        $backup = $cells[$i][$k];
                        $cells[$i][$k] = array_values(array_diff($cells[$i][$k], $cells[$i][$j]));
                        if(count($cells[$i][$k]) == 1) $meta[$i][$k]['setter'] = '-';
                        if(count($cells[$i][$k]) < 1) $cells[$i][$k] = $backup;
                        if(count($cells[$i][$k]) < $init) $reduced++;
                    }
                }
                // reduce col adjacent values
                for($k = 0; $k < $rows; $k++) {
                    // walk rows in column, exept self, and remove options
                    if($k != $i && count($cells[$k][$j]) > 1) {
                        $init = count($cells[$k][$j]);
                        $backup = $cells[$k][$j];
                        $cells[$k][$j] = array_values(array_diff($cells[$k][$j], $cells[$i][$j]));
                        if(count($cells[$k][$j]) == 1) $meta[$k][$j]['setter'] = '-';
                        if(count($cells[$k][$j]) < 1) $cells[$k][$j] = $backup;
                        if(count($cells[$k][$j]) < $init) $reduced++;
                    }
                }
                // reduce division adjacent values
                $rowdiv = floor($i / $div);
                $coldiv = floor($i / $div);
                for($m = $rowdiv; $m < (floor($rows/$div) + $rowdiv); $m++) {
                    for($n = $coldiv; $n < (floor($cols/$div) + $coldiv); $n++) {
                        // walk cells in division, exept self, and remove options
                        if($i != $m && $j != $n && count($cells[$m][$n]) > 1) {
                            $init = count($cells[$m][$n]);
                            $backup = $cells[$m][$n];
                            $cells[$m][$n] = array_values(array_diff($cells[$m][$n], $cells[$i][$j]));
                            if(count($cells[$m][$n]) == 1) $meta[$m][$n]['setter'] = '-';
                            if(count($cells[$m][$n]) < 1) $cells[$m][$n] = $backup;
                            if(count($cells[$m][$n]) < $init) $reduced++;
                        }
                    }
                }
                // reduce diagonal adjacent values
                if($i == $j || ($rows - $i) == $j) {
                    // reduce col adjacent values
                    for($k = 0; $k < $rows; $k++) {
                        // walk cels in diagonal, exept self, and remove options
                        if($k != $i && $k != $j && count($cells[$k][$k]) > 1) {
                            $init = count($cells[$i][$k]);
                            $backup = $cells[$i][$k];
                            $cells[$k][$k] = array_values(array_diff($cells[$k][$k], $cells[$i][$j]));
                            if(count($cells[$k][$k]) == 1) $meta[$k][$k]['setter'] = '-';
                            if(count($cells[$k][$k]) < 1) $cells[$k][$k] = $backup;
                            if(count($cells[$k][$k]) < $init) $reduced++;
                        }
                    }
                }
            }
            $cells[$i][$j] = array_values($cells[$i][$j]);
            if(count($cells[$i][$j]) == 1) $meta[$i][$j]['setter'] = '-';
            if($debug) sleep(1);
        }
    }
    return $reduced;
}

function rando($randoffset) {
    global $rows, $cols, $div, $totalcells, $debug;
    global $cells, $meta;
    // remove a random value
    if($GLOBALS['debug']) echo 'Rand Round' . PHP_EOL;
    $locallimit = 10;
    do {
        $rand_row = rand(0, $rows - 1);
        $rand_col = rand(0, $cols - 1);
        if($GLOBALS['debug']) echo '.';
        $locallimit--;
    } while((count($cells[$rand_row][$rand_col]) <= 1 || count($cells[$rand_row][$rand_col]) > 1 + $randoffset) && $locallimit > 0);
    if($locallimit > 0) {
        if($GLOBALS['debug']) echo PHP_EOL . 'Random Cell Found: (' . $rand_row . ', ' . $rand_col . '): ' . count($cells[$rand_row][$rand_col]) . PHP_EOL;
        if($GLOBALS['debug']) echo 'Existing Values: ' . implode(',', $cells[$rand_row][$rand_col]) . PHP_EOL;
        $attempt_value = array_values($cells[$rand_row][$rand_col])[rand(0, count($cells[$rand_row][$rand_col])-1)];
        $cells[$rand_row][$rand_col] = array($attempt_value);
        $meta[$rand_row][$rand_col]['setter'] = '?';
        if($GLOBALS['debug']) echo 'New Value: ' . implode(',', $cells[$rand_row][$rand_col]) . PHP_EOL;
    } else {
        $randoffset++;
    }
    return $randoffset;
}

function verify() {
    global $rows, $cols, $div, $totalcells, $debug;
    global $cells, $meta;
    $vissues = 0;
    $hissues = 0;
    $divissues = 0;
    $diagissues = 0;
    $set = 0;
    $rando = 0;
    $reduce = 0;
    // walk the cells
    for($i = 0; $i < $rows; $i++) {
        for($j = 0; $j < $cols; $j++) {
            // reduce row adjacent values
            for($k = 0; $k < $cols; $k++) {
                // walk columns in row, exept self, and remove options
                if($k != $j && $cells[$i][$j][0] == $cells[$i][$k][0]) $hissues++;
            }
            // reduce col adjacent values
            for($k = 0; $k < $rows; $k++) {
                // walk rows in column, exept self, and remove options
                if($k != $i && $cells[$i][$j][0] == $cells[$k][$j][0]) $vissues++;
            }
            // reduce division adjacent values
            $rowdiv = floor($i / $div);
            $coldiv = floor($i / $div);
            for($m = $rowdiv; $m < (floor($rows/$div) + $rowdiv); $m++) {
                for($n = $coldiv; $n < (floor($cols/$div) + $coldiv); $n++) {
                    // walk cells in division, exept self, and remove options
                    if($i != $m && $j != $n && $cells[$i][$j][0] == $cells[$m][$n][0]) $divissues++;
                }
            }
            // reduce diagonal adjacent values
            if($i == $j || ($rows - $i) == $j) {
                // reduce col adjacent values
                for($k = 0; $k < $rows; $k++) {
                    // walk cels in diagonal, exept self, and remove options
                    if($k != $i && $k != $j && $cells[$i][$j] == $cells[$k][$k]) $divissues++;
                }
            }
            if($meta[$i][$j]['setter'] == 'p') $set++;
            if($meta[$i][$j]['setter'] == '-') $reduce++;
            if($meta[$i][$j]['setter'] == '?') $rando++;
        }
    }
    echo 'Vertical: ' . $vissues . PHP_EOL;
    echo 'Horizontal: ' . $hissues . PHP_EOL;
    echo 'Division: ' . $divissues . PHP_EOL;
    echo 'Diagonal: ' . $diagissues . PHP_EOL;
    echo 'Total: ' . ($vissues + $hissues + $divissues + $diagissues) . PHP_EOL;
    echo 'Set: ' . $set . PHP_EOL;
    echo 'Random: ' . $rando . PHP_EOL;
    echo 'Reduce: ' . $reduce . PHP_EOL;
    echo 'Total: ' . ($set + $rando + $reduce) . PHP_EOL;
    return $vissues + $hissues + $divissues + $diagissues;
}

function solve($challenge) {
    global $rows, $cols, $div, $totalcells, $fullset, $debug;
    global $cells, $meta;
    global $sudoku_string;
    $sudoku_string = $challenge;
    if($GLOBALS['debug']) echo $sudoku_string . PHP_EOL;
    $cells = array();
    do {
        do {
            echo 'Starting Attempt' . PHP_EOL;
            echo '================' . PHP_EOL;
            if($GLOBALS['debug']) echo 'Generating Cells' . PHP_EOL;
            $cells = array();
            display(true);
            $randoffset = 0;
            $lastrando = false;
            $randobackup = $cells;
            $randobackup2 = $meta;
            do {  
                if(reduce() == 0) {
                    if(false && $lastrando) {
                        $cells = $randobackup;
                        $meta = $randobackup2;
                        echo '!';
                    } else {
                        echo '?';
                    }
                    $randoffset = rando($randoffset);
                    $lastrando = true;
                } else {
                    echo '-';
                    if($GLOBALS['debug']) echo 'Reduce Round' . PHP_EOL;
                    $lastrando = false;
                    $randobackup = $cells;
                    $randobackup2 = $meta;
                    $randoffset = 0;
                }
            } while (display(false) > 0);
            echo PHP_EOL;
            file_put_contents('attempt.json', json_encode($cells));
            //sleep(10);
        } while (display(false, true) != 0);
    } while (verify() != 0);
    display(false);
    echo 'Result: ' . output() . PHP_EOL;
    return output();
}

$conn = fsockopen ('127.0.0.1', 2222);
stream_set_timeout($conn, 2);
$line = '';
$state = 0;
$challenge = '';
while($state >= 0)
{
    $char = fread($conn, 1);
    echo $char;
    if($char == PHP_EOL) echo $state . ': ';
    $line .= trim($char);
    if($line == PHP_EOL) {
        $line = '';
    } else {
        if($state == 0)
        {
            if($line == '#') {
                $state = 1;

                fwrite($conn, 'sudo ku' . PHP_EOL);
                $line = '';
            }
        } elseif($state == 1) {
            if(strlen($line) == 81)
            {
                $state = 2;
                $challenge = $line;
                echo PHP_EOL . 'Challenge: ' . $challenge;
                $line = '';
            }
        } elseif($state == 2) {
            if($line == '?') {
                fwrite($conn, solve($challenge) . PHP_EOL);
                $line = '';
                $state = -1;
            }
        }
    }
}
echo '"' . $line . '"' . PHP_EOL;
echo '"' . fgets($conn) . '"' . PHP_EOL;


?>