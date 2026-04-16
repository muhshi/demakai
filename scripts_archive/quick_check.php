<?php
try {
    $pdo = new PDO('sqlite:database/database.sqlite');
    $stmt = $pdo->query("SELECT * FROM kbli2025s WHERE kode = '56101'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "FOUND: " . print_r($result, true);
    } else {
        echo "NOT FOUND 56101\n";
    }

    // Also check similar codes
    $stmt = $pdo->query("SELECT * FROM kbli2025s WHERE kode LIKE '5610%' LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Similar codes: " . print_r($results, true);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
