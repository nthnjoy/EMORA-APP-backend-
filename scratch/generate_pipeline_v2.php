<?php
/**
 * Generate DOCX: Alur Integrasi Data ke MongoDB Atlas
 * SIMPLIFIED — uses only standard w: namespace (no DrawingML)
 * Diagram = styled table with colored cells + arrow columns
 */

$out = __DIR__ . '/../docs/Alur_Integrasi_Data_MongoDB.docx';
$zip = new ZipArchive();
$zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// ── [Content_Types].xml ──────────────────────────────────────────
$zip->addFromString('[Content_Types].xml',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/word/document.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/docProps/core.xml"
    ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>');

// ── _rels/.rels ──────────────────────────────────────────────────
$zip->addFromString('_rels/.rels',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"
    Target="docProps/core.xml"/>
</Relationships>');

// ── word/_rels/document.xml.rels ─────────────────────────────────
$zip->addFromString('word/_rels/document.xml.rels',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
    Target="styles.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings"
    Target="settings.xml"/>
</Relationships>');

// ── docProps/core.xml ────────────────────────────────────────────
$zip->addFromString('docProps/core.xml',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties
  xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/">
  <dc:title>Alur Integrasi Data ke MongoDB Atlas - EMORA</dc:title>
  <dc:creator>PA-3 KEL-10 EMORA</dc:creator>
</cp:coreProperties>');

// ── word/settings.xml ────────────────────────────────────────────
$zip->addFromString('word/settings.xml',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="720"/>
</w:settings>');

// ── word/styles.xml ──────────────────────────────────────────────
$zip->addFromString('word/styles.xml',
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr>
      <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
      <w:sz w:val="22"/><w:szCs w:val="22"/>
      <w:lang w:val="id-ID"/>
    </w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr>
      <w:spacing w:after="100" w:line="240" w:lineRule="auto"/>
    </w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
  </w:style>
</w:styles>');


// ════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════

// Single paragraph
function p(string $text, bool $bold=false, int $sz=22,
           string $col='000000', string $align='left',
           int $spAfter=100, int $spBefore=0): string {
    $b   = $bold ? '<w:b/><w:bCs/>' : '';
    $esc = htmlspecialchars($text, ENT_XML1, 'UTF-8');
    return "<w:p>
      <w:pPr>
        <w:jc w:val=\"{$align}\"/>
        <w:spacing w:before=\"{$spBefore}\" w:after=\"{$spAfter}\"/>
      </w:pPr>
      <w:r><w:rPr>{$b}
        <w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>
        <w:color w:val=\"{$col}\"/>
      </w:rPr>
      <w:t xml:space=\"preserve\">{$esc}</w:t></w:r>
    </w:p>";
}

// Empty paragraph spacer
function spacer(int $spAfter=120): string {
    return "<w:p><w:pPr><w:spacing w:after=\"{$spAfter}\"/></w:pPr></w:p>";
}

// Horizontal rule
function hr(): string {
    return '<w:p><w:pPr>
      <w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="94A3B8"/></w:pBdr>
      <w:spacing w:after="200"/>
    </w:pPr></w:p>';
}

// Build a table cell with background color and multiple paragraphs
function tc(array $lines, int $width, string $bg='FFFFFF',
            bool $center=true, int $vMerge=0): string {
    $shd  = "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>";
    $tcW  = "<w:tcW w:w=\"{$width}\" w:type=\"dxa\"/>";
    $vA   = '<w:vAlign w:val="center"/>';
    $tcBd = '<w:tcBorders>
      <w:top    w:val="single" w:sz="4" w:color="CBD5E1"/>
      <w:left   w:val="single" w:sz="4" w:color="CBD5E1"/>
      <w:bottom w:val="single" w:sz="4" w:color="CBD5E1"/>
      <w:right  w:val="single" w:sz="4" w:color="CBD5E1"/>
    </w:tcBorders>';
    $tcPr = "<w:tcPr>{$tcW}{$shd}{$tcBd}{$vA}</w:tcPr>";

    $content = '';
    foreach ($lines as $idx => $ln) {
        [$txt, $bold, $sz, $col] = $ln;
        $b   = $bold ? '<w:b/><w:bCs/>' : '';
        $esc = htmlspecialchars($txt, ENT_XML1, 'UTF-8');
        $jc  = $center ? 'center' : 'left';
        $sp  = ($idx === count($lines)-1) ? 80 : 40;
        $spB = ($idx === 0) ? 80 : 0;
        $content .= "<w:p>
          <w:pPr>
            <w:jc w:val=\"{$jc}\"/>
            <w:spacing w:before=\"{$spB}\" w:after=\"{$sp}\"/>
          </w:pPr>
          <w:r><w:rPr>{$b}
            <w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>
            <w:color w:val=\"{$col}\"/>
          </w:rPr>
          <w:t xml:space=\"preserve\">{$esc}</w:t></w:r>
        </w:p>";
    }
    return "<w:tc>{$tcPr}{$content}</w:tc>";
}

// Arrow cell — just "→" centered
function arrowCell(int $width=400): string {
    $tcPr = "<w:tcPr>
      <w:tcW w:w=\"{$width}\" w:type=\"dxa\"/>
      <w:shd w:val=\"clear\" w:fill=\"F8FAFC\"/>
      <w:vAlign w:val=\"center\"/>
      <w:tcBorders>
        <w:top    w:val=\"none\"/>
        <w:left   w:val=\"none\"/>
        <w:bottom w:val=\"none\"/>
        <w:right  w:val=\"none\"/>
      </w:tcBorders>
    </w:tcPr>";
    return "<w:tc>{$tcPr}
      <w:p><w:pPr><w:jc w:val=\"center\"/><w:spacing w:before=\"0\" w:after=\"0\"/></w:pPr>
        <w:r><w:rPr>
          <w:sz w:val=\"52\"/><w:szCs w:val=\"52\"/>
          <w:color w:val=\"374151\"/>
        </w:rPr>
          <w:t>&#x2192;</w:t></w:r>
      </w:p>
    </w:tc>";
}


// ════════════════════════════════════════════════════════════
// BUILD DIAGRAM TABLE (pipeline nodes + arrows in one row)
// ════════════════════════════════════════════════════════════

// Node definitions: [fill, border-used-for-left-strip, lines[]]
// lines: [text, bold, halfPtSize, colorHex]
$nw = 1580; // node width in twips (dxa)
$aw = 360;  // arrow column width

$nodes = [
    [
        'FEF2F2',
        [
            ['DATA SOURCE',      true,  1600, '991B1B'],
            ['',                 false, 800,  'FFFFFF'],
            ['API CIS Del',      true,  1800, '7F1D1D'],
            ['jwt-api/do-auth',  false, 1400, '6B7280'],
            ['library-api',      false, 1400, '6B7280'],
            ['mahasiswa',        false, 1400, '6B7280'],
        ]
    ],
    [
        'EEF2FF',
        [
            ['PROCESSING',       true,  1600, '3730A3'],
            ['',                 false, 800,  'FFFFFF'],
            ['Laravel 12',       true,  1800, '312E81'],
            ['Controller',       false, 1400, '6B7280'],
            ['Service Layer',    false, 1400, '6B7280'],
            ['🔐 Enkripsi AES-256', false, 1300, '92400E'],
        ]
    ],
    [
        'F0FDF4',
        [
            ['DATABASE',         true,  1600, '14532D'],
            ['',                 false, 800,  'FFFFFF'],
            ['MongoDB Atlas',    true,  1800, '166534'],
            ['db: monitoring',   false, 1400, '6B7280'],
            ['6 Collections',    false, 1400, '6B7280'],
            ['578 dokumen',      false, 1400, '6B7280'],
        ]
    ],
    [
        'FAF5FF',
        [
            ['AI ENGINE',        true,  1600, '581C87'],
            ['',                 false, 800,  'FFFFFF'],
            ['Python FastAPI',   true,  1800, '6D28D9'],
            ['classify()',       false, 1400, '6B7280'],
            ['recommend()',      false, 1400, '6B7280'],
            ['generate-popup()', false, 1400, '6B7280'],
        ]
    ],
    [
        'F0F9FF',
        [
            ['DASHBOARD',        true,  1600, '075985'],
            ['',                 false, 800,  'FFFFFF'],
            ['Mobile + Web',     true,  1800, '0369A1'],
            ['Flutter App',      false, 1400, '6B7280'],
            ['Dashboard PA-3',   false, 1400, '6B7280'],
            ['Monitoring Mental',false, 1400, '6B7280'],
        ]
    ],
];

// Build the single diagram row
$cells = '';
foreach ($nodes as $i => [$bg, $lines]) {
    $cells .= tc($lines, $nw, $bg, true);
    if ($i < count($nodes) - 1) {
        $cells .= arrowCell($aw);
    }
}
$diagRow = "<w:tr>{$cells}</w:tr>";

// Table properties — no outer border, cells have inner borders
$totalW = (5 * $nw) + (4 * $aw);
$tblPrDiag = "<w:tblPr>
  <w:tblW w:w=\"{$totalW}\" w:type=\"dxa\"/>
  <w:tblLayout w:type=\"fixed\"/>
  <w:tblBorders>
    <w:top    w:val=\"none\"/>
    <w:left   w:val=\"none\"/>
    <w:bottom w:val=\"none\"/>
    <w:right  w:val=\"none\"/>
  </w:tblBorders>
  <w:tblCellMar>
    <w:top    w:w=\"80\"  w:type=\"dxa\"/>
    <w:left   w:w=\"80\"  w:type=\"dxa\"/>
    <w:bottom w:w=\"80\"  w:type=\"dxa\"/>
    <w:right  w:w=\"80\"  w:type=\"dxa\"/>
  </w:tblCellMar>
</w:tblPr>
<w:tblGrid>
  <w:gridCol w:w=\"{$nw}\"/>
  <w:gridCol w:w=\"{$aw}\"/>
  <w:gridCol w:w=\"{$nw}\"/>
  <w:gridCol w:w=\"{$aw}\"/>
  <w:gridCol w:w=\"{$nw}\"/>
  <w:gridCol w:w=\"{$aw}\"/>
  <w:gridCol w:w=\"{$nw}\"/>
  <w:gridCol w:w=\"{$aw}\"/>
  <w:gridCol w:w=\"{$nw}\"/>
</w:tblGrid>";

$diagTable = "<w:tbl>{$tblPrDiag}{$diagRow}</w:tbl>";

// ════════════════════════════════════════════════════════════
// BUILD KETERANGAN TABLE
// ════════════════════════════════════════════════════════════
function kRow(array $cols, array $widths, string $bg, bool $hdr=false): string {
    $tcs = '';
    foreach ($cols as $i => $txt) {
        $w    = $widths[$i] ?? 2000;
        $b    = $hdr ? '<w:b/><w:bCs/><w:color w:val="FFFFFF"/>' : '<w:color w:val="111827"/>';
        $sz   = $hdr ? 18 : 18;
        $esc  = htmlspecialchars($txt, ENT_XML1, 'UTF-8');
        $shd  = "<w:shd w:val=\"clear\" w:fill=\"{$bg}\"/>";
        $tcs .= "<w:tc>
          <w:tcPr><w:tcW w:w=\"{$w}\" w:type=\"dxa\"/>
            {$shd}
            <w:tcBorders>
              <w:top    w:val=\"single\" w:sz=\"4\" w:color=\"374151\"/>
              <w:left   w:val=\"single\" w:sz=\"4\" w:color=\"374151\"/>
              <w:bottom w:val=\"single\" w:sz=\"4\" w:color=\"374151\"/>
              <w:right  w:val=\"single\" w:sz=\"4\" w:color=\"374151\"/>
            </w:tcBorders>
          </w:tcPr>
          <w:p><w:pPr><w:spacing w:after=\"60\"/></w:pPr>
            <w:r><w:rPr>{$b}
              <w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>
            </w:rPr>
            <w:t xml:space=\"preserve\">{$esc}</w:t></w:r>
          </w:p>
        </w:tc>";
    }
    return "<w:tr>{$tcs}</w:tr>";
}

$kw = [1200, 1800, 6360];
$ktblPr = '<w:tblPr>
  <w:tblW w:w="9360" w:type="dxa"/>
  <w:tblBorders>
    <w:insideH w:val="single" w:sz="4" w:color="D1D5DB"/>
    <w:insideV w:val="single" w:sz="4" w:color="D1D5DB"/>
  </w:tblBorders>
</w:tblPr>';

$kRows  = kRow(['Tahap','Komponen','Keterangan'], $kw, '1E293B', true);
$kData  = [
    ['1','API CIS Del','Sumber autentikasi mahasiswa. Backend memanggil jwt-api/do-auth dan library-api untuk memperoleh profil, lalu disinkronkan ke MongoDB.'],
    ['2','Laravel 12.56.0','Menerima request, validasi Sanctum token, proses di Controller & Service. Pre-save: enkripsi AES-256 (Crypt), hash password, AI classify.'],
    ['3','MongoDB Atlas','Menyimpan 6 collection: users, daily_checkins, journal_texts, modules, notifications, personal_access_tokens. Total ±578 dokumen.'],
    ['4','Python FastAPI','AI Engine dipanggil sebelum data disimpan. Mengembalikan ai_level (0-3) dan ai_label ke collection journal_texts MongoDB.'],
    ['5','Flutter + Web PA-3','Menampilkan data monitoring ke mahasiswa (mobile) dan konselor (web). Web PA-3 menulis field mental_* langsung ke MongoDB.'],
];
$even = false;
foreach ($kData as $r) {
    $kRows .= kRow($r, $kw, $even ? 'F8FAFC' : 'FFFFFF');
    $even   = !$even;
}
$kTable = "<w:tbl>{$ktblPr}{$kRows}</w:tbl>";

// ════════════════════════════════════════════════════════════
// ASSEMBLE DOCUMENT
// ════════════════════════════════════════════════════════════
$sectPr = '<w:sectPr>
  <w:pgSz w:w="11906" w:h="16838"/>
  <w:pgMar w:top="1134" w:right="1134" w:bottom="1134"
           w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>
</w:sectPr>';

$body =
    p('EMORA — Alur Integrasi Data ke MongoDB Atlas',
      true, 28, '1E293B', 'center', 80, 0)
  . p('Dokumen Pengembangan Produk (DPP)  ·  PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
      false, 20, '64748B', 'center', 80)
  . hr()
  . p('Gambar 5.x  Alur Integrasi Data ke MongoDB Atlas',
      true, 22, '1E293B', 'center', 200, 80)
  . $diagTable
  . spacer(240)
  . p('Tabel 5.x  Keterangan Komponen Alur Integrasi',
      true, 22, '1E293B', 'left', 120, 80)
  . $kTable
  . spacer(240)
  . p('Sumber: Implementasi backend EMORA App, Laravel 12.56.0, MongoDB Atlas (db: monitoring), 2026.',
      false, 18, '6B7280', 'left', 0)
  . hr()
  . p('EMORA App  ·  PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
      false, 16, '9CA3AF', 'center', 0)
  . $sectPr;

$docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>' . $body . '</w:body>
</w:document>';

$zip->addFromString('word/document.xml', $docXml);
$zip->close();

echo "✅ Done: $out\n";
echo "   File size: " . round(filesize($out)/1024, 1) . " KB\n";
