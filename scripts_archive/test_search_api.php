<?php
$url = 'http://127.0.0.1:5001/search';
$queries = [
    "mencabut rumput liar di sawah",
    "01121"
];

foreach ($queries as $q) {
    echo "\nTesting query: $q\n";
    $data = [
        "query" => $q,
        "search" => "sql",
        "processing" => "none",
        "limit" => 5,
        "model" => "KBLI"
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { 
        echo "Error calling API\n";
    } else {
        $response = json_decode($result, true);
        echo "Found " . $response['total'] . " results:\n";
        foreach ($response['results'] as $r) {
            echo "- " . $r['kode'] . " | " . $r['judul'] . "\n";
            echo "  Contoh: " . json_encode($r['contoh_lapangan']) . "\n";
        }
    }
}
