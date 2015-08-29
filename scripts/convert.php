<?php

if ($argc < 3) {
    echo 'Usage: ./convert.php INPUT_FILE OUTPUT_DIR' . "\n";
    exit;
}

$filepath = $argv[1];
if (!file_exists($filepath)) {
    echo 'File not found.' . "\n";
    exit;
}
$output_dir = $argv[2];
if (!file_exists($output_dir)) {
    mkdir(__DIR__ . '/' . $output_dir, 0755, true);
}

$pattern = '#(\d+)年(\d+)月(\d+)日 (\d+)時(\d+)分,(.*),死亡(\d+);受傷(\d+),(.*)#';
$content = file_get_contents($filepath);

$data = array();
$year_count = 2015;
$month_count = 1;
$day_count = 1;

foreach (array_filter(explode("\n", $content)) as $line) {
    if (preg_match($pattern, $line, $matches)) {
        $year = $matches[1] + 1911;
        $month = $matches[2];
        $day = $matches[3];
        $time = $matches[4] . $matches[5];
        $datetime = intval($year . $month . $day . $time);
        $address = urlencode(trim($matches[6]));
        $death = intval($matches[7]);
        $injury = intval($matches[8]);
        $vehicles = explode('%3B', urlencode(trim($matches[9])));

        $obj = array(
            'datetime' => $datetime,
            'address' => $address,
            'death' => $death,
            'injury' => $injury,
            'vehicles' => $vehicles,
        );

        if (intval($day) != $day_count) {
            file_put_contents($output_dir . '/' . $year . sprintf("%02d", $month_count) . sprintf("%02d", $day_count) . '.json', urldecode(json_encode($data, JSON_PRETTY_PRINT)));
            $data = array();
            if (intval($month) != $month_count) {
                $month_count = intval($month);
            }
            $day_count = intval($day);
        }

        $data[] = $obj;
        //echo json_encode($json) . "\n";
    } else {
        echo $line . "\n";
    }
}

if (count($data)) {
    file_put_contents($output_dir . '/' . $year_count . sprintf("%02d", $month_count) . sprintf("%02d", $day_count) . '.json', urldecode(json_encode($data, JSON_PRETTY_PRINT)));
}
