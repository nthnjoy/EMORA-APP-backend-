<?php
require 'vendor/autoload.php';
$client = new MongoDB\Client('mongodb+srv://admin:monitoring2026@cluster0.jc0f5ag.mongodb.net/monitoring?retryWrites=true&w=majority&appName=Cluster0&authSource=admin');
$col = $client->monitoring->modules;
$docs = iterator_to_array($col->find());
echo json_encode($docs, JSON_PRETTY_PRINT);
