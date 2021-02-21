<?php
$debug = false;
$rows = 9;
$cols = $rows;
$div = 3;
$totalcells = $rows * $cols;
$fullset = array();
for($i = 0; $i < $rows; $i++) array_push($fullset, '' . ($i + 1));

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

function display($populate) {
    global $rows, $cols, $div, $totalcells, $fullset, $debug;
    global $cells;
    global $sudoku_string;
    $notfound = $totalcells;
    $error = false;
    echo 'Display' . PHP_EOL;
    if($populate) echo 'Populating' . PHP_EOL;
    if($populate) $cells = array();
    for($i = 0; $i < $rows; $i++) {
        if ($i % floor($rows/$div) == 0) echo str_pad('', (($cols*4) + $div + 1), '-') . PHP_EOL;
        if($populate) $cells[$i] = array();
        for($j = 0; $j < $cols; $j++) {
            $cell_string = substr($sudoku_string, $i*$cols + $j, 1);
            if ($j % $div == 0) echo '|';
            if($populate) {
                if($cell_string == '.') {
                    $cells[$i][$j] = array_values($fullset);
                } else {
                    $cells[$i][$j] = array($cell_string);
                }
            }
            if(count($cells[$i][$j]) == 1) {
                echo array_values($cells[$i][$j])[0];
                $notfound--;
            } elseif (count($cells[$i][$j]) > 1 && count($cells[$i][$j]) <= $rows) {
                echo '.';
            } else {
                echo '!';
                $error = true;
            }
            echo('(' . count($cells[$i][$j]) . ')');
        }
        echo '|' . PHP_EOL;
    }
    echo str_pad('', (($rows*4) + $div + 1), '-') . PHP_EOL;
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
    echo 'Possible Values: ' . $possible_values . PHP_EOL;
    return $possible_values - $totalcells;
}

function reduce() {
    global $rows, $cols, $div, $totalcells, $debug;
    global $cells;
    $reduced = 0;
    // walk the cells
    for($i = 0; $i < $rows; $i++) {
        for($j = 0; $j < $cols; $j++) {
            $possible_values = count($cells[$i][$j]);
            if($debug) echo '(' . $i . ', ' . $j . '): ' . PHP_EOL;
            if($debug) print_r($cells[$i][$j]);
            if($possible_values > 1) {
                $backup = $cells[$i][$j];
                // reduce row adjacent values
                for($k = 0; $k < $cols; $k++) {
                    // walk columns in row, exept self, and remove options
                    if($k != $j && count($cells[$i][$k]) == 1) $cells[$i][$j] = array_diff($cells[$i][$j], $cells[$i][$k]);
                }
                // reduce col adjacent values
                for($k = 0; $k < $rows; $k++) {
                    // walk rows in column, exept self, and remove options
                    if($k != $i && count($cells[$k][$j]) == 1) $cells[$i][$j] = array_diff($cells[$i][$j], $cells[$k][$j]);
                }
                // reduce division adjacent values
                $rowdiv = floor($i / $div);
                $coldiv = floor($i / $div);
                for($m = $rowdiv; $m < (floor($rows/$div) + $rowdiv); $m++) {
                    for($n = $coldiv; $n < (floor($cols/$div) + $coldiv); $n++) {
                        // walk cells in division, exept self, and remove options
                        if($i != $m && $j != $n && count($cells[$m][$n]) == 1) $cells[$i][$j] = array_diff($cells[$i][$j], $cells[$m][$n]);
                    }
                }
                // reduce diagonal adjacent values
                if($i == $j || ($rows - $i) == $j) {
                    // reduce col adjacent values
                    for($k = 0; $k < $rows; $k++) {
                        // walk cels in diagonal, exept self, and remove options
                        if($k != $i && $k != $j && count($cells[$k][$k]) == 1) $cells[$i][$j] = array_diff($cells[$i][$j], $cells[$k][$k]);
                    }
                }
                if(count($cells[$i][$j]) < 1) $cells[$i][$j] = $backup;
                $cells[$i][$j] = array_values($cells[$i][$j]);
            }
            $reduced += $possible_values - count($cells[$i][$j]);
            if($debug) sleep(1);
        }
    }
    return $reduced;
}

if(!isset($argv[1])) {
    echo 'Supply a string of 81 periods and single digit numbers for a 9x9 sudoku grid' . PHP_EOL;
    exit(1);
} elseif (!preg_match('#^[1-9\.]{' . $totalcells . '}$#', trim($argv[1]))) {
    echo 'Invalid string: ' . $argv[1] . PHP_EOL;
    echo 'Supply a string of 81 periods and single digit numbers for a 9x9 sudoku grid' . PHP_EOL;
    exit(1);
} elseif (strlen(trim($argv[1])) != $totalcells) {
    echo 'Invalid length: ' . strlen($argv[1]) . PHP_EOL;
    echo 'Supply a string of 81 periods and single digit numbers for a 9x9 sudoku grid' . PHP_EOL;
    exit(1);
} else {
    // prepare initial state
    $sudoku_string = trim($argv[1]);
    echo $sudoku_string . PHP_EOL;
    $cells = array();
    do {
        echo 'Starting Attempt' . PHP_EOL;
        echo '================' . PHP_EOL;
        echo 'Generating Cells' . PHP_EOL;
        $cells = array();
        display(true);
        $randoffset = 0;
        do {  
            if(reduce() == 0) {
                // remove a random value
                echo 'Rand Round' . PHP_EOL;
                $locallimit = 10;
                do {
                    $rand_row = rand(0, $rows - 1);
                    $rand_col = rand(0, $cols - 1);
                    echo '.';
                    $locallimit--;
                } while((count($cells[$rand_row][$rand_col]) <= 1 || count($cells[$rand_row][$rand_col]) > 1 + $randoffset) && $locallimit > 0);
                if($locallimit > 0) {
                    echo PHP_EOL . 'Random Cell Found: (' . $rand_row . ', ' . $rand_col . '): ' . count($cells[$rand_row][$rand_col]) . PHP_EOL;
                    echo 'Existing Values: ' . implode(',', $cells[$rand_row][$rand_col]) . PHP_EOL;
                    $attempt_value = array_values($cells[$rand_row][$rand_col])[rand(0, count($cells[$rand_row][$rand_col])-1)];
                    $cells[$rand_row][$rand_col] = array($attempt_value);
                    echo 'New Value: ' . implode(',', $cells[$rand_row][$rand_col]) . PHP_EOL;
                } else {
                    $randoffset++;
                }
            } else {
                echo 'Reduce Round' . PHP_EOL;
                $randoffset = 0;
            }
        } while (display(false) > 0);
        file_put_contents('attempt.json', json_encode($cells));
        //sleep(10);
    } while (display(false) != 0);
    echo 'Result: ' . output() . PHP_EOL;
}
?>