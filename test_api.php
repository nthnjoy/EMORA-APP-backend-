<?php
$data = json_encode(['content' => 'Ini test API curl lokal']);
$ch = curl_init('http://127.0.0.1:8000/api/stories');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    // Needs auth header, wait, it might require authentication
]);
$result = curl_exec($ch);
echo "Result: " . $result . "\n";
