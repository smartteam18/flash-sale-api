<?php
// grid buat task 2

$grid = [
    ['#', '#', '#', '#', '#', '#', '#', '#'],
    ['#', '.', '.', '.', '.', '.', '.', '#'],
    ['#', '.', '#', '#', '#', '.', '#', '#'],
    ['#', '.', '.', '.', '#', '.', '#', '#'],
    ['#', 'X', '#', '.', '.', '.', '.', '#'],
    ['#', '#', '#', '#', '#', '#', '#', '#']
];

echo "Grid awal:\n";
foreach ($grid as $row) {
    echo implode(" ", $row) . "\n";
}

// cari posisi X
$startX = 0;
$startY = 0;
for ($i = 0; $i < count($grid); $i++) {
    for ($j = 0; $j < count($grid[$i]); $j++) {
        if ($grid[$i][$j] == 'X') {
            $startX = $j;
            $startY = $i;
        }
    }
}

$A = 3;
$B = 2;
$C = 2;

$kemungkinan = [];

for ($up = 1; $up <= $A; $up++) {
    for ($right = 1; $right <= $B; $right++) {
        for ($down = 1; $down <= $C; $down++) {
            $x = $startX + $right;
            $y = $startY - $up + $down;

            if ($y < 0 || $y >= count($grid)) continue;
            if ($x < 0 || $x >= count($grid[$y])) continue;
            if ($grid[$y][$x] == '#') continue;
            if ($x == $startX && $y == $startY) continue;

            $kemungkinan[] = [
                'x' => $x,
                'y' => $y,
                'up' => $up,
                'right' => $right,
                'down' => $down
            ];
        }
    }
}

echo "\nKemungkinan lokasi item:\n";
foreach ($kemungkinan as $i => $k) {
    echo ($i+1) . ". (" . $k['x'] . ", " . $k['y'] . ") -> ";
    echo "Up " . $k['up'] . ", Right " . $k['right'] . ", Down " . $k['down'] . "\n";
}

// kasih tanda $ di grid (sesuai PDF)
$gridWithMark = $grid;
foreach ($kemungkinan as $k) {
    if ($gridWithMark[$k['y']][$k['x']] == '.') {
        $gridWithMark[$k['y']][$k['x']] = '$';
    }
}

echo "\nGrid dengan tanda \$:\n";
foreach ($gridWithMark as $row) {
    echo implode(" ", $row) . "\n";
}
?>