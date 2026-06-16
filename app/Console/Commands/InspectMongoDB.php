<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectMongoDB extends Command
{
    protected $signature   = 'db:inspect-mongo';
    protected $description = 'Inspect all MongoDB collections and dump real schema with field types';

    public function handle(): int
    {
        $db = DB::connection('mongodb')->getMongoDB();

        $collections = iterator_to_array($db->listCollections());
        $names = array_map(fn($c) => $c->getName(), $collections);
        sort($names);

        $this->info('DATABASE: ' . $db->getDatabaseName());
        $this->info('Collections (' . count($names) . '): ' . implode(', ', $names));
        $this->newLine();

        $output = [];

        foreach ($names as $colName) {
            $col   = $db->selectCollection($colName);
            $count = $col->countDocuments();

            $this->line("═══ COLLECTION: {$colName}  (total docs: {$count}) ═══");

            if ($count === 0) {
                $this->warn('  [EMPTY]');
                $this->newLine();
                $output[$colName] = ['count' => 0, 'fields' => []];
                continue;
            }

            $sampleSize = min(50, $count);
            $cursor     = $col->find([], ['limit' => $sampleSize]);

            $allFields  = [];
            $fieldTypes = [];
            $sample     = null;

            foreach ($cursor as $i => $doc) {
                if ($i === 0) $sample = $doc;
                foreach ($doc as $key => $value) {
                    $allFields[$key] = ($allFields[$key] ?? 0) + 1;
                    $t = $this->detectType($value);
                    if (!in_array($t, $fieldTypes[$key] ?? [])) {
                        $fieldTypes[$key][] = $t;
                    }
                }
            }

            // Sort: _id first
            uksort($allFields, fn($a, $b) =>
                $a === '_id' ? -1 : ($b === '_id' ? 1 : strcmp($a, $b))
            );

            $rows = [];
            foreach ($allFields as $field => $occ) {
                $rows[] = [
                    $field,
                    implode(' | ', $fieldTypes[$field]),
                    "{$occ}/{$sampleSize} (" . round($occ / $sampleSize * 100) . '%)',
                    $field === '_id' ? 'PK' : ($this->isFk($field) ? 'FK-ref' : ''),
                ];
            }

            $this->table(['Field', 'Type(s)', 'Occurrence', 'Key'], $rows);

            // Sample document
            $this->line('  [Sample document keys & values]:');
            if ($sample) {
                foreach ($sample as $k => $v) {
                    $display = $this->formatValue($v);
                    $this->line("    {$k}: {$display}");
                }
            }
            $this->newLine();

            $output[$colName] = [
                'count'  => $count,
                'fields' => array_map(fn($f, $t, $o) => [
                    'field' => $f,
                    'types' => $t,
                    'occ'   => $o,
                ], array_keys($allFields), array_values($fieldTypes), array_values($allFields)),
            ];
        }

        // Dump as JSON for easy parsing
        $jsonPath = base_path('scratch/real_schema_dump.json');
        file_put_contents($jsonPath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("✅ Schema dump saved to: {$jsonPath}");

        return 0;
    }

    private function detectType(mixed $v): string
    {
        return match (true) {
            $v instanceof \MongoDB\BSON\ObjectId      => 'ObjectId',
            $v instanceof \MongoDB\BSON\UTCDateTime   => 'DateTime',
            $v instanceof \MongoDB\BSON\PackedArray   => 'Array',
            $v instanceof \MongoDB\BSON\Document      => 'Object',
            is_array($v) && array_is_list($v)         => 'Array',
            is_array($v)                              => 'Object',
            is_bool($v)                               => 'Boolean',
            is_int($v)                                => 'Integer',
            is_float($v)                              => 'Double',
            is_string($v)                             => 'String',
            is_null($v)                               => 'null',
            default                                   => gettype($v),
        };
    }

    private function isFk(string $field): bool
    {
        return str_ends_with($field, '_id') && $field !== '_id';
    }

    private function formatValue(mixed $v): string
    {
        if ($v instanceof \MongoDB\BSON\ObjectId)    return 'ObjectId(' . (string)$v . ')';
        if ($v instanceof \MongoDB\BSON\UTCDateTime)  return 'DateTime(' . $v->toDateTime()->format('Y-m-d H:i:s') . ')';
        if ($v instanceof \MongoDB\BSON\PackedArray)  return 'Array[' . iterator_count($v) . ']';
        if (is_array($v) && array_is_list($v))        return 'Array[' . count($v) . ']';
        if (is_array($v))                             return 'Object{' . implode(', ', array_keys($v)) . '}';
        if (is_bool($v))                              return $v ? 'true' : 'false';
        if (is_null($v))                              return 'null';
        $str = (string)$v;
        return strlen($str) > 80 ? substr($str, 0, 77) . '...' : $str;
    }
}
