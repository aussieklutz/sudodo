<?php
$rows = 9;
$cols = $rows;
$div = 3;
$totalcells = $rows * $cols;

function display($populate) {
    global $rows, $cols, $div, $totalcells;
    global $cells;
    global $sudoku_string;
    for($i = 0; $i < $rows; $i++) {
        if ($i % floor($rows/$div) == 0) echo ('-' * ($rows + $div + 1)) . PHP_EOL;
        if($populate) $cells[$i] = array();
        for($j = 0; $j < $cols; $j++) {
            $cell_string = substr($sudoku_string, $i*$cols + $j, 1);
            if ($j % $div == 0) echo '|';
            echo $cell_string;
            if($populate) {
                if($cell_string == '.') {
                    $cells[$i][$j] = array(1,2,3,4,5,6,7,8,9);
                } else {
                    $cells[$i][$j] = array($cell_string);
                }
            }
        }
        echo '|' . PHP_EOL;
    }
}

function remainder() {
    global $rows, $cols, $div, $totalcells;
    global $cells;
    $possible_values = count($cells, COUNT_RECURSIVE);
    echo 'Possible Values: ' . $possible_values . PHP_EOL;
    return $possible_values - $totalcells;
}

function reduce() {
    global $rows, $cols, $div, $totalcells;
    global $cells;
    $reduced = 0;
    // walk the cells
    for($i = 0; $i < $rows; $i++) {
        for($j = 0; $j < $cols; $j++) {
            $possible_values = count($cells[$i][$j]);
            // reduce row adjacent values
            for($k = 0; $k < $cols; $k++) {
                // walk columns in row, exept self, and remove options
                if($k != $j) $cells[$i][$j] = array_diff($cells[$i][$k]);
            }
            // reduce col adjacent values
            for($k = 0; $k < $rows; $k++) {
                // walk rows in column, exept self, and remove options
                if($k != $i) $cells[$i][$j] = array_diff($cells[$k][$j]);
            }
            // reduce division adjacent values
            $rowdiv = floor($i / $div);
            $coldiv = floor($i / $div);
            for($m = $rowdiv; $m < (floor($rows/$div) + $rowdiv); $m++) {
                for($n = $coldiv; $n < (floor($cols/$div) + $coldiv); $n++) {
                    // walk cells in division, exept self, and remove options
                    if($i != $m && $j != $n) $cells[$i][$j] = array_diff($cells[$m][$n]);
                }
            }
            // reduce diagonal adjacent values
            if($i == $j || ($rows - $i) == $j) {
                // reduce col adjacent values
                for($k = 0; $k < $rows; $k++) {
                    // walk cels in diagonal, exept self, and remove options
                    if($k != $i && $k != $j) $cells[$i][$j] = array_diff($cells[$k][$k]);
                }
            }
            $reduced += $possible_values - count($cells[$i][$j]);
        }
        echo '|' . PHP_EOL;
    }
    return $reduced;
}

if(!isset($argv[1]) || preg_match('#^[1-9\.]{81}$#', $argv[1]))) {
    echo Supply a string of 81 periods and single digit numbers for a 9x9 sudoku grid;
    exit(1);
} else {
    // prepare initial state
    $sudoku_string = $argv[1];
    echo $sudoku_string . PHP_EOL;
    $cells = array();
    do {
        echo 'Starting Attempt' . PHP_EOL;
        echo '================' . PHP_EOL;
        echo 'Generating Cells' . PHP_EOL;
        $cells = array();
        display(true);
        do {  
            display(false);
            if(reduce() == 0) {
                // remove a random value
                echo 'Rand Round' . PHP_EOL;
                $rand_row = rand(0, $row - 1);
                $rand_col = rand(0, $col - 1);
                // if random cell isnt already finalised
                if(count($cells[$rand_row][$rand_col]) > 1) $cells[$rand_row][$rand_col] = array_diff($cells[$rand_row][$rand_col], array(rand(0, $row-1)));
            } else {
                echo 'Reduce Round' . PHP_EOL;
            }
        } while (remainder() > 0);
    } while (remainder() != 0);
    echo 'Result: ' . implode('', $cells) . PHP_EOL;
}
?>