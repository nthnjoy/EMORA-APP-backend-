<?php
/**
 * Generate DOCX manually via Office Open XML (OOXML)
 * No external library needed — builds ZIP structure directly
 */

$outputPath = __DIR__ . '/../docs/DPP_Diagram_Integrasi_API_MongoDB.docx';

// ── SVG diagram as inline base64 image (embedded) ────────────────
// We'll embed the diagram as an EMF-like description via DrawingML shapes

$zip = new ZipArchive();
if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create DOCX file.\n");
}

// ── [Content_Types].xml ──────────────────────────────────────────
$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/word/document.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
  <Override PartName="/word/settings.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
  <Override PartName="/word/numbering.xml"
    ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
  <Override PartName="/docProps/core.xml"
    ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>';
$zip->addFromString('[Content_Types].xml', $contentTypes);

// ── _rels/.rels ──────────────────────────────────────────────────
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
    Target="word/document.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"
    Target="docProps/core.xml"/>
</Relationships>';
$zip->addFromString('_rels/.rels', $rels);

// ── word/_rels/document.xml.rels ────────────────────────────────
$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
    Target="styles.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings"
    Target="settings.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering"
    Target="numbering.xml"/>
</Relationships>';
$zip->addFromString('word/_rels/document.xml.rels', $docRels);

// ── docProps/core.xml ────────────────────────────────────────────
$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
  xmlns:dc="http://purl.org/dc/elements/1.1/">
  <dc:title>DPP Diagram Integrasi API ke MongoDB Atlas</dc:title>
  <dc:creator>PA-3 KEL-10 EMORA</dc:creator>
  <dc:description>Dokumen Pengembangan Produk EMORA App</dc:description>
</cp:coreProperties>';
$zip->addFromString('docProps/core.xml', $core);

// ── word/settings.xml ────────────────────────────────────────────
$settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:defaultTabStop w:val="720"/>
  <w:compat/>
</w:settings>';
$zip->addFromString('word/settings.xml', $settings);

// ── word/numbering.xml ───────────────────────────────────────────
$numbering = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>';
$zip->addFromString('word/numbering.xml', $numbering);

echo "Building document content...\n";
require_once __DIR__ . '/docx_content.php';
$zip->addFromString('word/document.xml', buildDocument());
$zip->addFromString('word/styles.xml',   buildStyles());
$zip->close();

echo "✅ DOCX generated: $outputPath\n";
