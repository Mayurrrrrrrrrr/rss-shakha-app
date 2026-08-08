<?php
function devanagariToLatin($text) {
    $map = [
        // Vowels
        'अ'=>'a', 'आ'=>'aa', 'इ'=>'i', 'ई'=>'ee', 'उ'=>'u', 'ऊ'=>'oo', 'ऋ'=>'ri', 'ए'=>'e', 'ऐ'=>'ai', 'ओ'=>'o', 'औ'=>'au',
        // Consonants
        'क'=>'k', 'ख'=>'kh', 'ग'=>'g', 'घ'=>'gh', 'ङ'=>'ng',
        'च'=>'ch', 'छ'=>'chh', 'ज'=>'j', 'झ'=>'jh', 'ञ'=>'ny',
        'ट'=>'t', 'ठ'=>'th', 'ड'=>'d', 'ढ'=>'dh', 'ण'=>'n',
        'त'=>'t', 'थ'=>'th', 'द'=>'d', 'ध'=>'dh', 'न'=>'n',
        'प'=>'p', 'फ'=>'f', 'ब'=>'b', 'भ'=>'bh', 'म'=>'m',
        'य'=>'y', 'र'=>'r', 'ल'=>'l', 'व'=>'v', 'श'=>'sh', 'ष'=>'sh', 'स'=>'s', 'ह'=>'h',
        'ळ'=>'l', 'क्ष'=>'ksh', 'ज्ञ'=>'gy',
        // Matras
        'ा'=>'a', 'ि'=>'i', 'ी'=>'ee', 'ु'=>'u', 'ू'=>'oo', 'ृ'=>'ri', 'े'=>'e', 'ै'=>'ai', 'ो'=>'o', 'ौ'=>'au', 'ं'=>'n', 'ः'=>'h', 'ँ'=>'n',
        '्'=>'', // Halant removes the inherent 'a' sound, but for simple search matching, just ignoring it is fine
        '़'=>'' // Nuqta
    ];
    return strtr($text, $map);
}

echo devanagariToLatin("प्रणिल सावंत") . "\n";
echo devanagariToLatin("मयूर") . "\n";
echo devanagariToLatin("ज्ञानेश्वर") . "\n";
