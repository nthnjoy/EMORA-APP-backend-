<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectMongoFull extends Command
{
    protected $signature   = 'db:inspect-full';
    protected $description = 'Dump full real schema from MongoDB for PDM generation';

    public function handle(): int
    {
        $db    = DB::connection('mongodb')->getMongoDB();
        $cols  = iterator_to_array($db->listCollections());
        $names = array_map(fn($c) => $c->getName(), $cols);
        sort($names);

        $result = [];

        foreach ($names as $colName) {
            $col   = $db->selectCollection($colName);
            $count = $col->countDocuments();

            $fieldMap  = [];  // field => [types, occurrences]
            $sample    = null;
            $limit     = min(100, max($count, 1));
            $cursor    = $col->find([], ['limit' => $limit]);
            $docCount  = 0;

            foreach ($cursor as $i => $doc) {
                $docCount++;
                if ($i === 0) $sample = $doc;
                $this->walkDoc($doc, '', $fieldMap);
            }

            // Build field list
            $fields = [];
            foreach ($fieldMap as $path => $info) {
                $isPk  = $path === '_id';
                $isFk  = !$isPk && (str_ends_with($path, '_id') || $path === 'nim' || $path === 'tokenable_id');
                $pct   = $docCount > 0 ? round($info['count'] / $docCount * 100) : 0;
                $fields[] = [
                    'name'       => $path,
                    'types'      => array_values(array_unique($info['types'])),
                    'nullable'   => $pct < 100,
                    'occurrence' => $pct,
                    'is_pk'      => $isPk,
                    'is_fk'      => $isFk,
                ];
            }

            // Sort: _id first, then alphabetical
            usort($fields, fn($a,$b) =>
                $a['name'] === '_id' ? -1 : ($b['name'] === '_id' ? 1 : strcmp($a['name'], $b['name']))
            );

            // Sample doc as key=>value pairs (safe)
            $sampleData = [];
            if ($sample) {
                foreach ($sample as $k => $v) {
                    $sampleData[$k] = $this->safeVal($v);
                }
            }

            $result[$colName] = [
                'count'       => $count,
                'sampled'     => $docCount,
                'fields'      => $fields,
                'sample_doc'  => $sampleData,
            ];

            $this->info("✓ {$colName}: {$count} docs, " . count($fields) . " fields");
        }

        $out = base_path('scratch/full_schema.json');
        file_put_contents($out, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("\nDone → {$out}");
        return 0;
    }

    private function walkDoc(mixed $doc, string $prefix, array &$map): void
    {
        foreach ($doc as $key => $value) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            $type = $this->getType($value);

            if (!isset($map[$path])) {
                $map[$path] = ['types' => [], 'count' => 0];
            }
            $map[$path]['count']++;
            if (!in_array($type, $map[$path]['types'])) {
                $map[$path]['types'][] = $type;
            }

            // recurse into embedded objects (one level)
            if (($value instanceof \MongoDB\Model\BSONDocument ||
                 $value instanceof \MongoDB\BSON\Document ||
                 (is_array($value) && !array_is_list($value)))
                && $prefix === '') {
                $this->walkDoc($value, $path, $map);
            }
        }
    }

    private function getType(mixed $v): string
    {
        return match(true) {
            $v instanceof \MongoDB\BSON\ObjectId    => 'ObjectId',
            $v instanceof \MongoDB\BSON\UTCDateTime => 'DateTime',
            $v instanceof \MongoDB\Model\BSONArray  => 'Array',
            $v instanceof \MongoDB\BSON\PackedArray => 'Array',
            $v instanceof \MongoDB\Model\BSONDocument => 'Object',
            $v instanceof \MongoDB\BSON\Document    => 'Object',
            is_array($v) && array_is_list($v)       => 'Array',
            is_array($v)                            => 'Object',
            is_bool($v)                             => 'Boolean',
            is_int($v)                              => 'Integer',
            is_float($v)                            => 'Double',
            is_string($v)                           => 'String',
            is_null($v)                             => 'null',
            default                                 => gettype($v),
        };
    }

    private function safeVal(mixed $v): string
    {
        if ($v instanceof \MongoDB\BSON\ObjectId)    return 'ObjectId('.(string)$v.')';
        if ($v instanceof \MongoDB\BSON\UTCDateTime)  return $v->toDateTime()->format('Y-m-d H:i:s');
        if ($v instanceof \MongoDB\Model\BSONArray ||
            $v instanceof \MongoDB\BSON\PackedArray)  return '[Array]';
        if ($v instanceof \MongoDB\Model\BSONDocument||
            $v instanceof \MongoDB\BSON\Document)     return '{Object}';
        if (is_array($v))  return is_array($v) ? '[Array('.count($v).')]' : '';
        if (is_bool($v))   return $v ? 'true' : 'false';
        if (is_null($v))   return 'null';
        $s = (string)$v;
        return strlen($s) > 80 ? substr($s, 0, 77).'...' : $s;
    }
}
