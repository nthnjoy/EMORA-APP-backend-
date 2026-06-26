<?php
// Helper functions to build OOXML content

function w($tag, $attrs, $inner = '') {
    $attrStr = '';
    foreach ($attrs as $k => $v) $attrStr .= " w:$k=\"$v\"";
    if ($inner === null) return "<w:$tag$attrStr/>";
    return "<w:$tag$attrStr>$inner</w:$tag>";
}

function para($text, $style = 'Normal', $bold = false, $size = null, $color = null, $align = null, $spaceAfter = null) {
    $pPr = '';
    if ($style !== 'Normal') $pPr .= "<w:pStyle w:val=\"$style\"/>";
    if ($align)              $pPr .= "<w:jc w:val=\"$align\"/>";
    if ($spaceAfter !== null) $pPr .= "<w:spacing w:after=\"$spaceAfter\"/>";
    $pPrXml = $pPr ? "<w:pPr>$pPr</w:pPr>" : '';

    $rPr = '';
    if ($bold)  $rPr .= '<w:b/>';
    if ($size)  $rPr .= "<w:sz w:val=\"$size\"/><w:szCs w:val=\"$size\"/>";
    if ($color) $rPr .= "<w:color w:val=\"$color\"/>";
    $rPrXml = $rPr ? "<w:rPr>$rPr</w:rPr>" : '';

    $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
    $run = "<w:r>$rPrXml<w:t xml:space=\"preserve\">$escaped</w:t></w:r>";
    return "<w:p>$pPrXml$run</w:p>";
}

function emptyPara($spaceAfter = 120) {
    return "<w:p><w:pPr><w:spacing w:after=\"$spaceAfter\"/></w:pPr></w:p>";
}

function tableRow(array $cells, array $widths, $header = false, $bgColor = null) {
    $tcs = '';
    foreach ($cells as $i => $cell) {
        $w = $widths[$i] ?? 1800;
        $bg = $bgColor ? "<w:shd w:val=\"clear\" w:fill=\"$bgColor\"/>" : '';
        $tcPr = "<w:tcPr><w:tcW w:w=\"$w\" w:type=\"dxa\"/>$bg</w:tcPr>";
        $rPr  = $header ? '<w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr>' : '';
        $pPr  = "<w:pPr><w:spacing w:after=\"60\"/></w:pPr>";
        $escaped = htmlspecialchars($cell, ENT_XML1, 'UTF-8');
        $run  = "<w:r>$rPr<w:t xml:space=\"preserve\">$escaped</w:t></w:r>";
        $tcs .= "<w:tc>$tcPr<w:p>$pPr$run</w:p></w:tc>";
    }
    return "<w:tr>$tcs</w:tr>";
}

function buildDiagramTable() {
    // Diagram represented as a formatted table with borders + colors
    // Each cell = one node in the diagram

    $W  = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $tblW  = '<w:tblW w:w="9360" w:type="dxa"/>';
    $tblBd = '<w:tblBorders>
        <w:top    w:val="single" w:sz="4" w:color="CCCCCC"/>
        <w:left   w:val="single" w:sz="4" w:color="CCCCCC"/>
        <w:bottom w:val="single" w:sz="4" w:color="CCCCCC"/>
        <w:right  w:val="single" w:sz="4" w:color="CCCCCC"/>
        <w:insideH w:val="single" w:sz="4" w:color="CCCCCC"/>
        <w:insideV w:val="single" w:sz="4" w:color="CCCCCC"/>
      </w:tblBorders>';

    $tblPr = "<w:tblPr>$tblW$tblBd<w:tblLook w:val=\"0000\"/></w:tblPr>";

    // Column widths: Client | Backend | Database | External
    $cw = [1800, 2800, 2400, 2360]; // total = 9360 twips ≈ 16.5cm

    // ── Header row ──
    $hdr = fn($txt, $w) =>
        "<w:tc><w:tcPr><w:tcW w:w=\"$w\" w:type=\"dxa\"/>
            <w:shd w:val=\"clear\" w:fill=\"1E293B\"/>
         </w:tcPr>
         <w:p><w:pPr><w:jc w:val=\"center\"/><w:spacing w:after=\"60\"/></w:pPr>
           <w:r><w:rPr><w:b/><w:color w:val=\"FFFFFF\"/><w:sz w:val=\"18\"/></w:rPr>
             <w:t>$txt</w:t></w:r></w:p></w:tc>";

    $row0 = '<w:tr>'
        . $hdr('LAYER CLIENT',            $cw[0])
        . $hdr('LAYER BACKEND (Laravel 12)', $cw[1])
        . $hdr('LAYER DATABASE',          $cw[2])
        . $hdr('EXTERNAL SERVICES',       $cw[3])
        . '</w:tr>';

    // ── Helper: colored cell ──
    $cell = function($lines, $w, $bg, $txtColor = '111827', $bold = false) {
        $content = '';
        foreach ($lines as $li => $line) {
            $b    = ($bold && $li === 0) ? '<w:b/>' : '';
            $col  = ($li === 0) ? $txtColor : '374151';
            $sz   = ($li === 0) ? '18' : '16';
            $esc  = htmlspecialchars($line, ENT_XML1, 'UTF-8');
            $sp   = $li === count($lines)-1 ? '60' : '0';
            $content .= "<w:p>
              <w:pPr><w:spacing w:after=\"$sp\"/></w:pPr>
              <w:r><w:rPr>$b<w:color w:val=\"$col\"/>
                <w:sz w:val=\"$sz\"/><w:szCs w:val=\"$sz\"/></w:rPr>
                <w:t xml:space=\"preserve\">$esc</w:t></w:r></w:p>";
        }
        return "<w:tc>
          <w:tcPr><w:tcW w:w=\"$w\" w:type=\"dxa\"/>
            <w:shd w:val=\"clear\" w:fill=\"$bg\"/>
            <w:vAlign w:val=\"top\"/>
          </w:tcPr>$content</w:tc>";
    };

    // ── Row 1: top nodes ──
    $row1 = '<w:tr>'
        . $cell(['📱 Flutter Mobile App', 'Bearer Token Auth', 'HTTP Request/Response'], $cw[0], 'F0FDF4', '15803D', true)
        . $cell(['🔒 Sanctum Middleware', 'Validasi Bearer Token', 'auth:sanctum guard'], $cw[1], 'E0E7FF', '4338CA', true)
        . $cell(['🍃 MongoDB Atlas', 'db: monitoring', 'Cluster0 · M0 Tier'], $cw[2], 'DCFCE7', '15803D', true)
        . $cell(['🏛 CIS Del API', 'jwt-api/do-auth (POST)', 'library-api/mahasiswa (GET)'], $cw[3], 'FEF2F2', 'B91C1C', true)
        . '</w:tr>';

    // ── Row 2: main content ──
    $row2 = '<w:tr>'
        . $cell(['🌐 Web Dashboard PA-3', 'Akses langsung MongoDB', 'Menulis mental_* & modules', 'tanpa melalui Laravel'], $cw[0], 'EFF6FF', '1D4ED8', true)
        . $cell(['Controllers', '• AuthController', '• MoodController', '• StoryController', '• ModuleController', '• NotificationController', '• AiController'], $cw[1], 'F5F3FF', '5B21B6', true)
        . $cell(['Collections:', 'users         (37 docs)', 'daily_checkins (162 docs)', 'journal_texts   (71 docs)', 'modules          (5 docs)', 'notifications   (19 docs)', 'personal_access_tokens (284)'], $cw[2], 'F0FDF4', '166534', true)
        . $cell(['🤖 AI Engine (Python)', 'Hugging Face Space', 'api/classify  →  ai_level', 'api/recommend →  quote', 'api/generate-popup', 'api/ping  (health check)'], $cw[3], 'FAF5FF', '6D28D9', true)
        . '</w:tr>';

    // ── Row 3: pre-save process ──
    $row3 = '<w:tr>'
        . $cell(['Alur Data:',  'Request masuk', '→ validasi token', '→ proses di controller', '→ pre-save', '→ simpan ke DB'], $cw[0], 'F8FAFC', '374151', true)
        . $cell(['Services + Pre-save:', '• AiService::classifyText()', '• ExternalApiService (CIS)', '🔐 Crypt::encryptString()', '   (enkripsi jurnal AES-256)', '🔑 Hash::make(password)', '🔄 User::updateOrCreate()'], $cw[1], 'FFFBEB', '92400E', true)
        . $cell(['Operasi DB:', 'users        → C · R · U', 'daily_checkins → C · R · U', 'journal_texts  → C · R', 'modules        → R only', 'notifications  → R · U', 'access_tokens  → C · D'], $cw[2], 'F0FDF4', '14532D', true)
        . $cell(['Keterangan:', 'C = CREATE (insert)', 'R = READ   (query)', 'U = UPDATE (update)', 'D = DELETE (remove)', '', 'Driver: mongodb/laravel-mongodb 5.7'], $cw[3], 'F8FAFC', '374151', true)
        . '</w:tr>';

    // ── Row 4: alur panah description ──
    $arrowBg = 'F1F5F9';
    $row4 = '<w:tr><w:tc>
        <w:tcPr>
          <w:gridSpan w:val="4"/>
          <w:tcW w:w="9360" w:type="dxa"/>
          <w:shd w:val="clear" w:fill="' . $arrowBg . '"/>
        </w:tcPr>
        <w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="60"/></w:pPr>
          <w:r><w:rPr><w:b/><w:sz w:val="16"/></w:rPr>
            <w:t>Alur Integrasi: Client  →  Backend (Laravel 12)  →  MongoDB Atlas (db: monitoring)</w:t></w:r>
        </w:p>
        <w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="60"/></w:pPr>
          <w:r><w:rPr><w:sz w:val="16"/><w:color w:val="555555"/></w:rPr>
            <w:t>Framework: Laravel 12.56.0  ·  Auth: Laravel Sanctum 4.0  ·  DB Driver: mongodb/laravel-mongodb 5.7  ·  Enkripsi: AES-256 (Laravel Crypt)</w:t></w:r>
        </w:p>
        </w:tc></w:tr>';

    return "<w:tbl>$tblPr$row0$row1$row2$row3$row4</w:tbl>";
}

function buildDocument(): string {
    $ns  = 'xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
            xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
            xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';

    $body = '';

    // ── COVER HEADER ──────────────────────────────────────────────
    $body .= para('EMORA — Emotional Monitoring and Reflective Activity',
        'Normal', true, 28, '1E3A5C', 'center', 80);
    $body .= para('Dokumen Pengembangan Produk (DPP)',
        'Normal', false, 22, '374151', 'center', 40);
    $body .= para('PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
        'Normal', false, 20, '6B7280', 'center', 240);

    // ── HORIZONTAL RULE (via bottom border paragraph) ─────────────
    $body .= '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="1E293B"/></w:pBdr><w:spacing w:after="200"/></w:pPr></w:p>';

    // ── SECTION TITLE ─────────────────────────────────────────────
    $body .= para('Diagram Integrasi API ke MongoDB Atlas',
        'Normal', true, 26, '1E293B', 'center', 60);
    $body .= para('Gambar 5.x — Alur integrasi data dari REST API melalui Laravel 12 menuju MongoDB Atlas',
        'Normal', false, 18, '6B7280', 'center', 200);

    // ── DIAGRAM TABLE ─────────────────────────────────────────────
    $body .= buildDiagramTable();
    $body .= emptyPara(200);

    // ── DESCRIPTION ───────────────────────────────────────────────
    $body .= para('Keterangan Diagram', 'Normal', true, 22, '1E293B', null, 80);
    $body .= para(
        'Diagram di atas menggambarkan alur integrasi data dari lapisan client (Flutter Mobile App dan Web Dashboard PA-3) '
        .'melalui backend Laravel 12 menuju database MongoDB Atlas (db: monitoring). '
        .'Setiap request yang masuk divalidasi oleh Laravel Sanctum, kemudian diproses oleh controller yang sesuai. '
        .'Sebelum data disimpan ke database, sistem melakukan beberapa proses kritis: enkripsi konten jurnal menggunakan AES-256 (Laravel Crypt), '
        .'hashing password (bcrypt), serta pemanggilan AI Engine untuk klasifikasi level kesehatan mental.',
        'Normal', false, 20, '374151', null, 120);

    // ── COMPONENT TABLE ───────────────────────────────────────────
    $body .= para('Tabel 5.x — Keterangan Komponen Integrasi API', 'Normal', true, 22, '1E293B', null, 80);

    // Table header + rows
    $tblW  = '<w:tblW w:w="9360" w:type="dxa"/>';
    $tblBd = '<w:tblBorders>
        <w:top    w:val="single" w:sz="4" w:color="374151"/>
        <w:left   w:val="single" w:sz="4" w:color="374151"/>
        <w:bottom w:val="single" w:sz="4" w:color="374151"/>
        <w:right  w:val="single" w:sz="4" w:color="374151"/>
        <w:insideH w:val="single" w:sz="4" w:color="D1D5DB"/>
        <w:insideV w:val="single" w:sz="4" w:color="D1D5DB"/>
      </w:tblBorders>';
    $tblPr2 = "<w:tblPr>$tblW$tblBd</w:tblPr>";

    $rows = '';
    // header
    $rows .= tableRow(
        ['Komponen', 'Teknologi', 'Peran dalam Integrasi'],
        [1800, 2000, 5560], true, '1E293B');
    // data rows
    $data = [
        ['Client — Mobile', 'Flutter (Dart)',
         'Mengirim request HTTP ke REST API menggunakan Bearer Token Sanctum. Menerima response JSON dan menampilkan data ke pengguna.'],
        ['Client — Web', 'Web Dashboard PA-3',
         'Mengakses MongoDB Atlas secara langsung untuk membaca data mahasiswa dan menulis field mental_* (hasil analisis AI) serta modul edukasi.'],
        ['Backend', 'Laravel 12.56.0',
         'Menerima dan memvalidasi request, memproses logika bisnis via Controller dan Service Layer, mengeksekusi operasi CRUD ke MongoDB Atlas.'],
        ['Auth', 'Laravel Sanctum 4.0',
         'Mengelola token autentikasi Bearer. Token disimpan di collection personal_access_tokens MongoDB dan dihapus saat logout.'],
        ['Database', 'MongoDB Atlas (db: monitoring)',
         'Menyimpan 6 collection: users, daily_checkins, journal_texts, modules, notifications, personal_access_tokens. Total ±578 dokumen.'],
        ['Enkripsi', 'Laravel Crypt (AES-256-CBC)',
         'Konten jurnal (collection journal_texts) dienkripsi menggunakan Laravel Crypt::encryptString() sebelum disimpan. Didekripsi otomatis saat dibaca.'],
        ['CIS Del API', 'REST API Eksternal',
         'Sumber autentikasi utama. Setiap login memvalidasi kredensial ke server CIS Institut Del, lalu data profil mahasiswa disinkronkan ke collection users MongoDB.'],
        ['AI Engine', 'Python FastAPI (Hugging Face)',
         'Dipanggil sebelum data jurnal dan mood disimpan. Menghasilkan ai_level (0–3) dan ai_label yang disimpan ke collection journal_texts MongoDB.'],
    ];
    $even = false;
    foreach ($data as $row) {
        $bg = $even ? 'F8FAFC' : 'FFFFFF';
        $rows .= tableRow($row, [1800, 2000, 5560], false, $bg);
        $even = !$even;
    }

    $body .= "<w:tbl>$tblPr2$rows</w:tbl>";
    $body .= emptyPara(200);

    // ── FOOTER ────────────────────────────────────────────────────
    $body .= '<w:p><w:pPr><w:pBdr><w:top w:val="single" w:sz="4" w:space="1" w:color="CCCCCC"/></w:pBdr><w:spacing w:before="200"/></w:pPr></w:p>';
    $body .= para('EMORA App  ·  Dokumen Pengembangan Produk  ·  PA-3 KEL-10  ·  Institut Teknologi Del  ·  2026',
        'Normal', false, 16, '9CA3AF', 'center', 0);

    // ── PAGE SETUP (A4) ───────────────────────────────────────────
    $sectPr = '<w:sectPr>
        <w:pgSz w:w="11906" w:h="16838"/>
        <w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="709" w:footer="709" w:gutter="0"/>
      </w:sectPr>';

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>' . $body . $sectPr . '</w:body>
</w:document>';
}

function buildStyles(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
        <w:sz w:val="20"/>
        <w:szCs w:val="20"/>
        <w:lang w:val="id-ID"/>
      </w:rPr>
    </w:rPrDefault>
    <w:pPrDefault>
      <w:pPr>
        <w:spacing w:after="160" w:line="276" w:lineRule="auto"/>
      </w:pPr>
    </w:pPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:styleId="Normal" w:default="1">
    <w:name w:val="Normal"/>
  </w:style>
</w:styles>';
}
