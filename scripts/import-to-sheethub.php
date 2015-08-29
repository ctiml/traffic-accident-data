<?php

if ($argc < 2) {
    echo 'Usage: ./import-to-sheethub.php INPUT_FILE' . "\n";
    exit;
}

$filepath = $argv[1];
if (!file_exists($filepath)) {
    echo 'File not found.' . "\n";
    exit;
}

$pattern = '#(\d+)年(\d+)月(\d+)日 (\d+)時(\d+)分,(.*),死亡(\d+);受傷(\d+),(.*)#';
$content = file_get_contents($filepath);

$sheet = '/ctiml/2015_警政署交通事故資料';

$ch = curl_init("https://sheethub.com{$sheet}/insert?access_token=" . getenv('access_token'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$data = array();
$current_date = null;

foreach (array_filter(explode("\n", $content)) as $line) {
    if (preg_match($pattern, $line, $matches)) {
        $year = $matches[1] + 1911;
        $month = $matches[2];
        $day = $matches[3];
        $date = $year . $month . $day;
        $time = $matches[4] . $matches[5];
        $address = trim($matches[6]);
        $death = intval($matches[7]);
        $injury = intval($matches[8]);
        $vehicles = trim($matches[9]);

        $row = array(
            $date,
            $time,
            $address,
            $death,
            $injury,
            $vehicles,
        );
        
        if ($current_date and $current_date != $date) {
            // commit data
            $params = array();
            $params[] = 'data=' . urlencode(json_encode($data, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
            $content = curl_exec($ch);
            $obj = json_decode($content);
            error_log($content);
            if (is_null($obj->data->count)) {
                print_r($row);
            }
            $current_date = $date;
            $data = array();
        }
        $data[] = $row;
    } else {
        echo $line . "\n";
    }
}

if (count($data)) {
    $params = array();
    $params[] = 'data=' . urlencode(json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $params));
    $content = curl_exec($ch);
    $obj = json_decode($content);
    error_log($content);
}
