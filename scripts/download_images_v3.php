<?php
// Script to download personality images using shell WGET
$images = [
    1 => 'https://upload.wikimedia.org/wikipedia/commons/e/ee/Dr._Hedgevar.jpg',
    2 => 'https://upload.wikimedia.org/wikipedia/commons/6/6a/M_S_Golwalkar.jpg',
    3 => 'https://upload.wikimedia.org/wikipedia/commons/b/b8/Balasaheb_deoras.jpg',
    6 => 'https://upload.wikimedia.org/wikipedia/commons/7/72/Dr._mohan_rao_Bhagwat1.jpg',
    7 => 'https://upload.wikimedia.org/wikipedia/commons/e/ea/Dattatreya_Hosabale.jpg',
    8 => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Deendayal_Upadhyaya_2018_stamp_of_India_%28cropped%29.jpg',
    9 => 'https://upload.wikimedia.org/wikipedia/commons/0/05/Sheshadriji_01.jpg',
    10 => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/LaxmiBaiKelkar.jpg'
];

$dir = '/var/www/html/sanghasthan/assets/images/personalities/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($images as $id => $url) {
    $ext = pathinfo($url, PATHINFO_EXTENSION) ?: 'jpg';
    $filename = "p_{$id}.{$ext}";
    $path = $dir . $filename;
    echo "Downloading $url to $path...\n";
    
    $cmd = "wget -U 'Mozilla/5.0' -O " . escapeshellarg($path) . " " . escapeshellarg($url);
    exec($cmd, $output, $return_var);

    if ($return_var === 0) {
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}
?>
