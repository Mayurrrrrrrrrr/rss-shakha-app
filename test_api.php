<?php
$word = "pranil";
$url = "https://inputtools.google.com/request?text=" . urlencode($word) . "&itc=hi-t-i0-und&num=1";
$response = file_get_contents($url);
print_r($response);
