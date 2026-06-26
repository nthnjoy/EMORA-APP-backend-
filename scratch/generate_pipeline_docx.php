<?php
/**
 * Generate DOCX: Alur Integrasi Data ke MongoDB Atlas
 * Pipeline diagram: 5 node horizontal dengan DrawingML shapes
 */

$outputPath = __DIR__ . '/../docs/Alur_Integrasi_Data_MongoDB.docx';
$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create DOCX.\n");
}

// ── [Content_Types].xml ──────────────────────────────────────────
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
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
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"
    Target="docProps/core.xml"/>
</Relationships>');

// ── word/_rels/document.xml.rels ─────────────────────────────────
$zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
    Target="styles.xml"/>
  <Relationship Id="rId2"
    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings"
    Target="settings.xml"/>
</Relationships>');

// ── docProps/core.xml ────────────────────────────────────────────
$zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties
  xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/">
  <dc:title>Alur Integrasi Data ke MongoDB Atlas - EMORA App</dc:title>
  <dc:creator>PA-3 KEL-10 EMORA</dc:creator>
</cp:coreProperties>');

// ── word/settings.xml ────────────────────────────────────────────
$zip->addFromString('word/settings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="720"/>
</w:settings>');

// ── word/styles.xml ──────────────────────────────────────────────
$zip->addFromString('word/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault><w:rPr>
      <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
      <w:sz w:val="22"/><w:szCs w:val="22"/>
      <w:lang w:val="id-ID"/>
    </w:rPr></w:rPrDefault>
    <w:pPrDefault><w:pPr>
      <w:spacing w:after="120" w:line="276" w:lineRule="auto"/>
    </w:pPr></w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
  </w:style>
</w:styles>');

// ── Helpers ──────────────────────────────────────────────────────
function p($text, $bold=false, $size=22, $color='000000', $align='left', $spAfter=120) {
    $b   = $bold ? '<w:b/>' : '';
    $esc = htmlspecialchars($text, ENT_XML1, 'UTF-8');
    return "<w:p>
      <w:pPr><w:jc w:val=\"$align\"/>
        <w:spacing w:after=\"$spAfter\"/>
      </w:pPr>
      <w:r><w:rPr>$b<w:sz w:val=\"$size\"/>
        <w:szCs w:val=\"$size\"/>
        <w:color w:val=\"$color\"/>
      </w:rPr>
      <w:t xml:space=\"preserve\">$esc</w:t></w:r>
    </w:p>";
}

function hrLine() {
    return '<w:p><w:pPr>
      <w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="1E293B"/></w:pBdr>
      <w:spacing w:after="160"/>
    </w:pPr></w:p>';
}

// ── DrawingML: satu shape kotak berwarna dengan teks ─────────────
// posisi dalam EMU (1 cm = 360000 EMU)
// cx=lebar, cy=tinggi, x=left offset, y=top offset
function shape($id, $x, $y, $cx, $cy, $fillColor, $borderColor, $lines) {
    // lines: array of [text, bold, size(pt*100 = halfpts), colorHex]
    $txBody = '';
    foreach ($lines as $ln) {
        [$t, $b, $sz, $col] = $ln;
        $bTag = $b ? '<a:b/>' : '';
        $esc  = htmlspecialchars($t, ENT_XML1, 'UTF-8');
        $txBody .= "
        <a:p>
          <a:pPr algn=\"ctr\"/>
          <a:r>
            <a:rPr lang=\"id-ID\" sz=\"$sz\" dirty=\"0\">$bTag
              <a:solidFill><a:srgbClr val=\"$col\"/></a:solidFill>
              <a:latin typeface=\"Calibri\"/>
            </a:rPr>
            <a:t>$esc</a:t>
          </a:r>
        </a:p>";
    }

    return "
    <wps:wsp>
      <wps:cNvSpPr><a:spLocks noChangeArrowheads=\"1\"/></wps:cNvSpPr>
      <wps:spPr>
        <a:xfrm>
          <a:off x=\"$x\" y=\"$y\"/>
          <a:ext cx=\"$cx\" cy=\"$cy\"/>
        </a:xfrm>
        <a:prstGeom prst=\"roundRect\"><a:avLst/></a:prstGeom>
        <a:solidFill><a:srgbClr val=\"$fillColor\"/></a:solidFill>
        <a:ln w=\"19050\"><a:solidFill><a:srgbClr val=\"$borderColor\"/></a:solidFill></a:ln>
      </wps:spPr>
      <wps:txbx>
        <w:txbxContent>
          <w:p><w:pPr><w:jc w:val=\"center\"/></w:pPr></w:p>
        </w:txbxContent>
      </wps:txbx>
      <wps:bodyPr rot=\"0\" vert=\"horz\" wrap=\"square\" lIns=\"91440\" rIns=\"91440\"
                  tIns=\"45720\" bIns=\"45720\" anchor=\"ctr\" anchorCtr=\"1\">
        <a:normAutofit/>
      </wps:bodyPr>
      <wps:lstStyle/>
      <wps:txbx>
        <w:txbxContent>
          <w:p><w:pPr><w:jc w:val=\"center\"/>
            <w:spacing w:before=\"0\" w:after=\"0\"/>
          </w:pPr></w:p>
          $txBody
        </w:txbxContent>
      </wps:txbx>
    </wps:wsp>";
}


// ── Arrow connector between shapes ───────────────────────────────
function arrow($id, $x1, $y, $x2, $cx=254000, $cy=100000) {
    // horizontal line with arrowhead
    $w = $x2 - $x1;
    return "
    <wps:wsp>
      <wps:cNvSpPr><a:spLocks noChangeArrowheads=\"0\"/></wps:cNvSpPr>
      <wps:spPr>
        <a:xfrm>
          <a:off x=\"$x1\" y=\"$y\"/>
          <a:ext cx=\"$w\" cy=\"12700\"/>
        </a:xfrm>
        <a:prstGeom prst=\"line\"><a:avLst/></a:prstGeom>
        <a:noFill/>
        <a:ln w=\"38100\">
          <a:solidFill><a:srgbClr val=\"374151\"/></a:solidFill>
          <a:tailEnd type=\"arrow\" w=\"med\" len=\"med\"/>
        </a:ln>
      </wps:spPr>
      <wps:bodyPr/>
      <wps:lstStyle/>
    </wps:wsp>";
}

// ── DIAGRAM drawing (Word Canvas via mc:AlternateContent) ─────────
// Canvas width = 16 cm = 5760000 EMU (fits inside A4 margins)
// Each node: w=2400000 EMU (~6.7cm), h=2000000 (~5.5cm)
// Gap between nodes (arrows): 400000 EMU
// Total = 5 nodes * 2400000 + 4 gaps * 400000 = 13600000  → scale down
// We use a 14400000 wide canvas, node w=2000000, gap=600000

$cw  = 14400000; // canvas width  EMU
$ch  =  3200000; // canvas height EMU
$nw  =  2100000; // node width
$nh  =  2600000; // node height
$gap =  480000;  // arrow gap
$yOff=   300000; // top padding

// x positions for 5 nodes
$x = [];
$x[0] = 0;
for ($i = 1; $i < 5; $i++) {
    $x[$i] = $x[$i-1] + $nw + $gap;
}

// Arrow x positions (right edge of prev node → left edge of next)
$ay  = $yOff + intval($nh/2) - 6350; // vertical center of arrow

// node data: [fill, border, lines[text, bold, halfPtSize, colorHex]]
$nodes = [
    [
        'FEF2F2', 'FCA5A5',
        [
            ['DATA SOURCE', true,  1400, '991B1B'],
            ['',            false, 900,  '991B1B'],
            ['API CIS Del', true,  1300, '7F1D1D'],
            ['jwt-api/do-auth',      false, 1000, '6B7280'],
            ['library-api/mahasiswa', false, 1000, '6B7280'],
        ]
    ],
    [
        'EEF2FF', 'A5B4FC',
        [
            ['PROCESSING',  true,  1400, '3730A3'],
            ['',            false, 900,  '3730A3'],
            ['Laravel 12',  true,  1300, '312E81'],
            ['Controller · Service',  false, 1000, '6B7280'],
            ['Enkripsi AES-256',      false, 1000, '6B7280'],
            ['Hash · updateOrCreate', false, 1000, '6B7280'],
        ]
    ],
    [
        'F0FDF4', '86EFAC',
        [
            ['DATABASE',     true,  1400, '14532D'],
            ['',             false, 900,  '14532D'],
            ['MongoDB Atlas',true,  1300, '166534'],
            ['db: monitoring',       false, 1000, '6B7280'],
            ['6 Collections',        false, 1000, '6B7280'],
            ['578 dokumen',          false, 1000, '6B7280'],
        ]
    ],
    [
        'FAF5FF', 'D8B4FE',
        [
            ['AI ENGINE',    true,  1400, '581C87'],
            ['',             false, 900,  '581C87'],
            ['Python FastAPI',true, 1300, '6D28D9'],
            ['Hugging Face Space',   false, 1000, '6B7280'],
            ['classify · recommend', false, 1000, '6B7280'],
            ['generate-popup',       false, 1000, '6B7280'],
        ]
    ],
    [
        'F0F9FF', '7DD3FC',
        [
            ['DASHBOARD',    true,  1400, '075985'],
            ['',             false, 900,  '075985'],
            ['Mobile + Web', true,  1300, '0369A1'],
            ['Flutter App',         false, 1000, '6B7280'],
            ['Dashboard PA-3',      false, 1000, '6B7280'],
            ['Monitoring Mental',   false, 1000, '6B7280'],
        ]
    ],
];

$shapes = '';
foreach ($nodes as $i => $nd) {
    $shapes .= shape($i+1, $x[$i], $yOff, $nw, $nh, $nd[0], $nd[1], $nd[2]);
}
// arrows between nodes
for ($i = 0; $i < 4; $i++) {
    $ax1 = $x[$i] + $nw;
    $ax2 = $x[$i+1];
    $shapes .= arrow(100+$i, $ax1, $ay, $ax2);
}

// Wrap in mc:AlternateContent → wpc:wpc canvas
$drawing = '
<w:p>
  <w:pPr><w:jc w:val="center"/><w:spacing w:after="200"/></w:pPr>
  <w:r>
    <w:rPr><w:noProof/></w:rPr>
    <w:drawing>
      <wp:inline distT="0" distB="0" distL="0" distR="0"
        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing">
        <wp:extent cx="' . $cw . '" cy="' . ($nh + $yOff*2) . '"/>
        <wp:effectExtent l="0" t="0" r="0" b="0"/>
        <wp:docPr id="1" name="Pipeline Diagram"/>
        <wp:cNvGraphicFramePr/>
        <a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
          <a:graphicData uri="http://schemas.microsoft.com/office/drawing/2010/wordprocessingCanvas">
            <wpc:wpc xmlns:wpc="http://schemas.microsoft.com/office/drawing/2010/wordprocessingCanvas"
                     xmlns:wps="http://schemas.microsoft.com/office/drawing/2010/wordprocessingShape"
                     xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">
              ' . $shapes . '
            </wpc:wpc>
          </a:graphicData>
        </a:graphic>
      </wp:inline>
    </w:drawing>
  </w:r>
</w:p>';

// ── Keterangan tabel ─────────────────────────────────────────────
function tRow($cells, $widths, $bg='FFFFFF', $hdr=false) {
    $tcs = '';
    foreach ($cells as $i => $c) {
        $w   = $widths[$i] ?? 2000;
        $b   = $hdr ? '<w:b/><w:color w:val="FFFFFF"/>' : '';
        $shd = "<w:shd w:val=\"clear\" w:fill=\"$bg\"/>";
        $esc = htmlspecialchars($c, ENT_XML1, 'UTF-8');
        $tcs .= "<w:tc><w:tcPr><w:tcW w:w=\"$w\" w:type=\"dxa\"/>$shd</w:tcPr>
          <w:p><w:pPr><w:spacing w:after=\"60\"/></w:pPr>
            <w:r><w:rPr>$b<w:sz w:val=\"18\"/><w:szCs w:val=\"18\"/></w:rPr>
              <w:t xml:space=\"preserve\">$esc</w:t></w:r>
          </w:p></w:tc>";
    }
    return "<w:tr>$tcs</w:tr>";
}

$tblBd = '<w:tblBorders>
    <w:top    w:val="single" w:sz="4" w:color="374151"/>
    <w:left   w:val="single" w:sz="4" w:color="374151"/>
    <w:bottom w:val="single" w:sz="4" w:color="374151"/>
    <w:right  w:val="single" w:sz="4" w:color="374151"/>
    <w:insideH w:val="single" w:sz="4" w:color="D1D5DB"/>
    <w:insideV w:val="single" w:sz="4" w:color="D1D5DB"/>
  </w:tblBorders>';
$tblPr = "<w:tblPr><w:tblW w:w=\"9360\" w:type=\"dxa\"/>$tblBd</w:tblPr>";

$rows  = tRow(['Tahap','Komponen','Keterangan'],[1500,2000,5860],'1E293B',true);
$data  = [
    ['1. Data Source',  'API CIS Del',          'Menyediakan data autentikasi mahasiswa. Backend memanggil jwt-api/do-auth dan library-api untuk memperoleh profil mahasiswa.'],
    ['2. Processing',   'Laravel 12.56.0',       'Menerima request, memvalidasi token Sanctum, mengeksekusi Controller dan Service. Proses pre-save: enkripsi AES-256, hash password, AI classify.'],
    ['3. Database',     'MongoDB Atlas',         'Menyimpan dan melayani data dari 6 collection: users, daily_checkins, journal_texts, modules, notifications, personal_access_tokens (±578 dokumen).'],
    ['4. AI Engine',    'Python FastAPI',        'Dipanggil sebelum data jurnal/mood disimpan. Mengembalikan ai_level (0–3) dan ai_label yang disimpan ke collection journal_texts.'],
    ['5. Dashboard',    'Flutter + Web PA-3',    'Menampilkan data monitoring kepada mahasiswa (mobile) dan konselor (web). Web PA-3 juga menulis field mental_* langsung ke MongoDB.'],
];
$even = false;
foreach ($data as $r) {
    $rows .= tRow($r, [1500,2000,5860], $even ? 'F8FAFC' : 'FFFFFF');
    $even  = !$even;
}
$table = "<w:tbl>$tblPr$rows</w:tbl>";

// ── Assemble document.xml ─────────────────────────────────────────
$sectPr = '<w:sectPr>
    <w:pgSz w:w="11906" w:h="16838"/>
    <w:pgMar w:top="1134" w:right="1134" w:bottom="1134"
             w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>
  </w:sectPr>';

$body =
    p('EMORA — Alur Integrasi Data ke MongoDB Atlas', true, 28, '1E293B', 'center', 80)
  . p('Dokumen Pengembangan Produk (DPP)  ·  PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
        false, 20, '6B7280', 'center', 80)
  . hrLine()
  . p('Gambar 5.x — Alur Integrasi Data ke MongoDB Atlas (EMORA App)',
        true, 22, '374151', 'center', 160)
  . $drawing
  . p('Tabel 5.x — Keterangan Komponen Alur Integrasi', true, 22, '374151', null, 120)
  . $table
  . '<w:p><w:pPr><w:spacing w:after="400"/></w:pPr></w:p>'
  . p('Sumber: Implementasi backend EMORA App, Laravel 12.56.0, MongoDB Atlas (db: monitoring).',
        false, 18, '6B7280', null, 0)
  . hrLine()
  . p('EMORA App  ·  PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
        false, 16, '9CA3AF', 'center', 0)
  . $sectPr;

$docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document
  xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
  xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
  xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
  xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
  xmlns:wps="http://schemas.microsoft.com/office/drawing/2010/wordprocessingShape"
  xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
  mc:Ignorable="w14 wp14">
  <w:body>' . $body . '</w:body>
</w:document>';

$zip->addFromString('word/document.xml', $docXml);
$zip->close();
echo "✅ Done: $outputPath\n";
