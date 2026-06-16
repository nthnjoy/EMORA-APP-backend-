<?php
/**
 * MongoDB Real Schema Inspector
 * Connects directly to MongoDB Atlas and samples each collection
 * to discover ALL fields actually present in the database (including
 * fields added by other projects/teams that are not in our models).
 */

$dsn = 'mongodb+srv://admin:monitoring2026@cluster0.jc0f5ag.mongodb.net/monitoring?retryWrites=true&w=majority&appName=Cluster0&authSource=admin';
$dbName = 'monitoring';

try {
    $client = new MongoDB\Client($dsn);
    $db = $client->selectDatabase($dbName);

    // List all collections
    $collections = iterator_to_array($db->listCollections());
    $collectionNames = array_map(fn($c) => $c->getName(), $collections);
    sort($collectionNames);

    echo "=== DATABASE: $dbName ===\n";
    echo "Collections found: " . count($collectionNames) . "\n";
    echo implode(", ", $collectionNames) . "\n\n";

    foreach ($collectionNames as $colName) {
        $col = $db->selectCollection($colName);

        // Count documents
        $count = $col->countDocuments();
        echo "─────────────────────────────────────────\n";
        echo "COLLECTION: $colName  (docs: $count)\n";
        echo "─────────────────────────────────────────\n";

        if ($count === 0) {
            echo "  [EMPTY]\n\n";
            continue;
        }

        // Sample up to 20 documents to discover all fields
        $sampleSize = min(20, $count);
        $cursor = $col->find([], ['limit' => $sampleSize]);

        $allFields = [];
        $fieldTypes = [];

        foreach ($cursor as $doc) {
            $docArray = (array) $doc;
            foreach ($docArray as $key => $value) {
                if (!isset($allFields[$key])) {
                    $allFields[$key] = 0;
                }
                $allFields[$key]++;

                // Detect type
                $type = getMongoType($value);
                if (!isset($fieldTypes[$key])) {
                    $fieldTypes[$key] = [];
                }
                if (!in_array($type, $fieldTypes[$key])) {
                    $fieldTypes[$key][] = $type;
                }
            }
        }

        // Sort: _id first, then alphabetical
        uksort($allFields, function($a, $b) {
            if ($a === '_id') return -1;
            if ($b === '_id') return 1;
            return strcmp($a, $b);
        });

        // Print field table
        printf("  %-30s %-20s %-10s\n", "FIELD", "TYPE(s)", "OCCURRENCE");
        printf("  %-30s %-20s %-10s\n", str_repeat('-', 29), str_repeat('-', 19), str_repeat('-', 9));
        foreach ($allFields as $field => $occurrences) {
            $typeStr = implode('|', $fieldTypes[$field]);
            $pct = round(($occurrences / $sampleSize) * 100);
            $marker = ($field === '_id') ? ' PK' : '';
            printf("  %-30s %-20s %d/%d (%d%%)%s\n",
                $field, $typeStr, $occurrences, $sampleSize, $pct, $marker);
        }

        // Show one sample document (pretty)
        echo "\n  [Sample document]:\n";
        $sample = $col->findOne([]);
        printDoc($sample, 2);
        echo "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

function getMongoType($value): string {
    if ($value instanceof MongoDB\BSON\ObjectId) return 'ObjectId';
    if ($value instanceof MongoDB\BSON\UTCDateTime) return 'DateTime';
    if ($value instanceof MongoDB\BSON\Document) return 'Document';
    if ($value instanceof MongoDB\BSON\PackedArray) return 'Array';
    if (is_array($value)) return 'Array';
    if (is_bool($value)) return 'Boolean';
    if (is_int($value)) return 'Integer';
    if (is_float($value)) return 'Double';
    if (is_string($value)) return 'String';
    if (is_null($value)) return 'null';
    return gettype($value);
}

function printDoc($doc, int $indent = 0): void {
    $pad = str_repeat('  ', $indent);
    foreach ($doc as $key => $value) {
        if ($value instanceof MongoDB\BSON\ObjectId) {
            echo "$pad$key: ObjectId(" . (string)$value . ")\n";
        } elseif ($value instanceof MongoDB\BSON\UTCDateTime) {
            echo "$pad$key: DateTime(" . $value->toDateTime()->format('Y-m-d H:i:s') . ")\n";
        } elseif ($value instanceof MongoDB\BSON\PackedArray || (is_array($value) && array_is_list($value))) {
            $arr = is_array($value) ? $value : iterator_to_array($value);
            if (count($arr) === 0) {
                echo "$pad$key: []\n";
            } else {
                echo "$pad$key: [" . implode(', ', array_slice(array_map('strval', $arr), 0, 5)) . (count($arr) > 5 ? '...' : '') . "]\n";
            }
        } elseif ($value instanceof MongoDB\BSON\Document || (is_array($value) && !array_is_list($value))) {
            echo "$pad$key: {\n";
            printDoc($value, $indent + 1);
            echo "$pad}\n";
        } elseif (is_string($value) && strlen($value) > 80) {
            echo "$pad$key: \"" . substr($value, 0, 77) . "...\"\n";
        } else {
            $display = is_null($value) ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : $value);
            echo "$pad$key: $display\n";
        }
    }
}
