<?php
// Improved script to download personality images using CURL
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
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RSS-Shakha-App/1.0 (Contact: admin@example.com)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $content = curl_exec($ch);
    curl_close($ch);

    if ($content) {
        file_put_contents($path, $content);
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}
?>
