<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SampleReceptionController extends Controller
{
    public function show()
    {
        return view('sample-reception.upload');
    }

    public function parse(Request $request)
    {
        $request->validate([
            'form_file_main' => 'required|file|mimes:pdf,doc,docx,xlsx,xls,csv|max:20480',
            'form_file_secondary' => 'nullable|file|mimes:pdf,xlsx,xls,csv|max:20480',
        ]);

        $mainFile = $request->file('form_file_main');
        $mainResult = $this->processFile($mainFile, true);

        $secondaryResult = null;
        if ($request->hasFile('form_file_secondary')) {
            $secondaryFile = $request->file('form_file_secondary');
            $secondaryResult = $this->processFile($secondaryFile, false);
        }

        // Aggregate counts across both docs for convenience
        $counts = HtmlPreview::summarizeCounts([
            $mainResult['sample_data'] ?? [],
            $secondaryResult['sample_data'] ?? []
        ]);

        return view('sample-reception.upload', [
            'mainParsed' => $mainResult['fields'],
            'secondaryParsed' => $secondaryResult['fields'] ?? [],
            'mainPreview' => $mainResult['preview'],
            'secondaryPreview' => $secondaryResult['preview'] ?? null,
            'mainFilename' => $mainResult['filename'],
            'secondaryFilename' => $secondaryResult['filename'] ?? null,
            'counts' => $counts,
        ]);
    }

    private function processFile($file, bool $isMain): array
    {
        $filename = $file->getClientOriginalName();
        $path = $file->store('uploads', 'public');
        $extension = strtolower($file->getClientOriginalExtension());
        $fullPath = storage_path('app/public/' . $path);

        // Parse
        $parser = DocumentParserFactory::create($extension);
        $parsed = $parser->parse($fullPath, $isMain);

        // Process
        $processor = new ActlabsFormProcessor();
        $processed = $processor->process($parsed);

        // Preview
        $preview = $this->generatePreview($extension, $path, $processed['raw'], $processed['sample_data']);

        return [
            'fields' => $processed['fields'],
            'preview' => $preview,
            'filename' => $filename,
            'sample_data' => $processed['sample_data'] ?? []
        ];
    }

    private function generatePreview(string $extension, string $path, string $rawText, array $sampleData = []): string
    {
        if ($extension === 'pdf') {
            return '<iframe src="' . asset('storage/' . $path) . '" class="document-viewer"></iframe>';
        }

        if (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $parts = [];
            if (!empty($sampleData)) {
                $parts[] = '<div class="section-header"><i class="bi bi-table"></i> Detected Sample Table</div>' 
                         . HtmlPreview::tableFromSampleData($sampleData);
            }
            try {
                $parts[] = '<div class="section-header"><i class="bi bi-file-spreadsheet"></i> Sheet Preview</div>' 
                         . HtmlPreview::tableFromSpreadsheet(storage_path('app/public/' . $path));
            } catch (\Throwable $e) {
                // CSV fallback if reader chokes
                try {
                    $csvPath = HtmlPreview::toTempCsv(storage_path('app/public/' . $path));
                    $parts[] = '<div class="section-header"><i class="bi bi-file-spreadsheet"></i> Sheet Preview (CSV Fallback)</div>' 
                             . HtmlPreview::tableFromSpreadsheet($csvPath);
                } catch (\Throwable $e2) {
                    $parts[] = '<pre>' . e($rawText) . '</pre>';
                }
            }
            return implode("\n", array_filter($parts));
        }

        return '<pre>' . e($rawText) . '</pre>';
    }
}

// ---------------------------------------------
// Config & Patterns
// ---------------------------------------------
class ActlabsFormConfig
{
    public const REQUIRED_FIELDS = [
        'Carrier', 'Waybill #', '# of Packages', '# of Samples', 'Priority',
        'Confirmation of Sample Receipt', 'Special Instructions/Comments',
        'Client Name', 'Client Batch #', 'Shipment #', 'Quote #, PO #, Proforma #',
        'Project', 'Company', 'Address', 'Attn', 'Phone', 'Fax', 'E-mail',
        'Additional Report To', 'Payment Method', 'Credit Card Info',
        'Reporting & Invoicing Preferences', 'Method of Sample Return'
    ];

    // More forgiving, multi-line aware patterns
    public const FIELD_PATTERNS = [
        'Carrier' => [
            '/\bCarrier\b\s*[:\-]?\s*(.+?)(?=\s{2,}|\n\s*(Waybill|#\s*of\s*Packages|#\s*of\s*Samples)\b|$)/ims',
            '/^Carrier:\s*(.+)$/im',
        ],
        'Waybill #' => [
            '/\bWaybill\b\s*(?:#|No|Number)?\s*[:\-]?\s*(.+?)(?=\s{2,}|\n\s*(#\s*of\s*Packages|#\s*of\s*Samples|Priority)\b|$)/ims',
            '/^Waybill\s*#?\s*[:\-]?\s*(.+)$/im',
        ],
        '# of Packages' => [
            '/(?:#|Number)\s*of\s*Packages\s*[:\-]?\s*(\d+)/ims',
            '/Packages\s*[:\-]?\s*(\d+)/i',
        ],
        '# of Samples' => [
            '/(?:#|Number)\s*of\s*Samples\s*[:\-]?\s*(\d+)/ims',
            '/Samples\s*[:\-]?\s*(\d+)/i',
        ],
        'Priority' => [
            '/Priority\s*[:\-]?\s*(RUSH|Normal|URGENT|EMERGENCY|STANDARD|ROUTINE)/i',
            '/^Priority:\s*(.+)$/im',
        ],
        'Confirmation of Sample Receipt' => [
            '/Confirmation\s+of\s+Sample\s+Receipt\s*[:\-]?\s*(Yes|No)/i',
            '/Sample\s+Receipt\s+Confirmation\s*[:\-]?\s*(Yes|No)/i',
        ],
        'Special Instructions/Comments' => [
            '/Special\s+Instructions?\s*\/?\s*Comments?\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:|\Z)/ims',
            '/Comments?\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:|\Z)/ims',
        ],
        'Client Name' => [
            '/Client\s+Name\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
            '/^Client:\s*(.+)$/im',
        ],
        'Client Batch #' => [
            '/Client\s+Batch\s*#?\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
            '/Batch\s*#?\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Shipment #' => [
            '/Shipment\s*#?\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Quote #, PO #, Proforma #' => [
            '/(?:Quote|PO|Proforma)\s*#?\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Project' => [
            '/^\s*Project\s*[:\-]\s*(.+)$/im',
            '/Project\s+Name\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Company' => [
            '/^\s*Company\s*[:\-]\s*(.+)$/im',
            '/Company\s+Name\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Address' => [
            '/^\s*Address\s*[:\-]\s*(.+)$/im',
            '/Mailing\s+Address\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:)/ims',
        ],
        'Attn' => [
            '/^\s*Attn\.?\s*[:\-]\s*(.+)$/im',
            '/Attention\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Phone' => [
            '/^\s*Phone\s*[:\-]\s*([+\d][\d\s\-().]+)$/im',
            '/Tel\.?\s*[:\-]?\s*([+\d][\d\s\-().]+)/im',
        ],
        'Fax' => [
            '/^\s*Fax\s*[:\-]\s*([+\d][\d\s\-().]+)$/im',
        ],
        'E-mail' => [
            '/^\s*E-?mail\s*[:\-]\s*([^\s].+?)$/im',
            '/Email\s*[:\-]?\s*([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/im',
        ],
        'Additional Report To' => [
            '/Additional\s+Report\s+To\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:|\Z)/ims',
        ],
        'Payment Method' => [
            '/Payment\s+Method\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
            '/Method\s+of\s+Payment\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        'Credit Card Info' => [
            '/Credit\s+Card\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:|\Z)/ims',
        ],
        'Reporting & Invoicing Preferences' => [
            '/Reporting\s*[&\/]?\s*Invoicing\s+Preferences?\s*[:\-]?\s*(.+?)(?=\n\n|\n[A-Z][a-z]+\s*:|\Z)/ims',
        ],
        'Method of Sample Return' => [
            '/Method\s+of\s+Sample\s+Return\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
            '/Sample\s+Return\s*[:\-]?\s*(.+?)(?=\s{2,}|\n|$)/im',
        ],
        // Sometimes Prep/Analysis appear as free text blocks not in tables
        'Prep Code' => [
            '/\bPrep\.?\s*Code\b\s*[:\-]?\s*(.+?)(?=\s{2,}|\n\s*(Analysis|Elements|Sample)\b|$)/ims',
        ],
        'Analysis Code / Elements' => [
            '/\b(Analysis\s*Code|Elements)\b\s*[:\-]?\s*(.+?)(?=\s{2,}|\n\s*(Sample|Prep)\b|$)/ims',
        ],
    ];

    public const PAYMENT_PATTERNS = [
        'Payment included' => '/Payment\s+is\s+included/i',
        'New Credit Card' => '/Charge\s+to\s+NEW\s+Credit\s+Card/i',
        'Credit Card on file' => '/Charge\s+to\s+Credit\s+Card\s+on\s+file/i',
        'Established Credit' => '/Credit\s+has\s+been\s+established/i',
    ];

    public const SAMPLE_HEADER_MAPPINGS = [
        '/Sample\s+Numbers/i' => 'Sample Numbers',
        '/Sample\s+Type/i' => 'Sample Type',
        '/Prep\.?\s*Code/i' => 'Prep Code',
        '/Analysis\s+Code|Elements/i' => 'Analysis Code / Elements',
        '/No\.?\s*of\s*Samples|#\s*Samples|Qty/i' => '# of Samples',
    ];

    public const SKIP_PATTERNS = [
        '/^Page/i', '/^Rev\./i', '/Activation\s+Laboratories/i', '/Authorized\s+Signature/i',
        '/Client\s+Name/i', '/Sample\s+Numbers|Sample\s+Type|Prep\.?\s*Code|Analysis\s+Code|Elements/i'
    ];
}

// ---------------------------------------------
// Parser factory & interfaces
// ---------------------------------------------
interface DocumentParserInterface
{ public function parse(string $filePath, bool $isMain = true): array; }

class DocumentParserFactory
{
    public static function create(string $extension): DocumentParserInterface
    {
        return match ($extension) {
            'pdf' => new PdfParser(),
            'doc', 'docx' => new WordParser(),
            'xlsx', 'xls', 'csv' => new SpreadsheetParser(),
            default => throw new \InvalidArgumentException("Unsupported file type: $extension"),
        };
    }
}

abstract class BaseDocumentParser implements DocumentParserInterface
{
    protected function initializeFields(): array
    { return array_fill_keys(ActlabsFormConfig::REQUIRED_FIELDS, ''); }

    protected function extractSampleHeaders(string $text): array
    {
        $headers = [];
        foreach (ActlabsFormConfig::SAMPLE_HEADER_MAPPINGS as $pattern => $fieldName) {
            if (preg_match($pattern, $text)) { $headers[] = $fieldName; }
        }
        return $headers;
    }
}

// ---------------------------------------------
// PDF
// ---------------------------------------------
class PdfParser extends BaseDocumentParser
{
    public function parse(string $filePath, bool $isMain = true): array
    {
        try {
            $text = PdfTextExtractor::extract($filePath);
            $fields = $this->initializeFields();

            $extractedFields = ActlabsFieldExtractor::extract($text);
            $fields = array_replace($fields, $extractedFields);
            ActlabsFieldExtractor::sweepKeyValues($text, $fields);

            $sampleData = SampleDataExtractor::extract($text);

            return [ 'fields' => $fields, 'raw' => $text, 'sample_data' => $sampleData ];
        } catch (\Throwable $e) {
            return [ 'fields' => $this->initializeFields(), 'raw' => 'Error parsing PDF: ' . $e->getMessage(), 'sample_data' => [] ];
        }
    }
}

class PdfTextExtractor
{
    public static function extract(string $filePath): string
    {
        if (self::hasPdftotext()) {
            $tmp = tempnam(sys_get_temp_dir(), 'pdftxt');
            @unlink($tmp);
            $cmd = sprintf('pdftotext -layout -nopgbrk %s %s 2>&1', escapeshellarg($filePath), escapeshellarg($tmp));
            @shell_exec($cmd);
            if (is_file($tmp)) {
                $text = @file_get_contents($tmp) ?: '';
                @unlink($tmp);
                if (strlen(trim($text)) > 0) return $text;
            }
        }
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    private static function hasPdftotext(): bool
    { $which = trim((string) @shell_exec('which pdftotext')); return $which !== ''; }
}

// ---------------------------------------------
// Word
// ---------------------------------------------
class WordParser extends BaseDocumentParser
{
    public function parse(string $filePath, bool $isMain = true): array
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $textBlocks = [];
            $fields = $this->initializeFields();
            $sampleData = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                        $this->processTable($element, $fields, $sampleData);
                    } else {
                        $this->processTextElement($element, $textBlocks);
                    }
                }
            }

            $text = implode("\n", $textBlocks);
            $fields = array_replace($fields, ActlabsFieldExtractor::extract($text));
            ActlabsFieldExtractor::sweepKeyValues($text, $fields);
            if (empty($sampleData)) { $sampleData = SampleDataExtractor::extract($text); }

            return [ 'fields' => $fields, 'raw' => $text, 'sample_data' => $sampleData ];
        } catch (\Throwable $e) {
            return [ 'fields' => $this->initializeFields(), 'raw' => 'Error parsing Word document: ' . $e->getMessage(), 'sample_data' => [] ];
        }
    }

    private function processTable($table, array &$fields, array &$sampleData): void
    {
        $tableRows = $table->getRows();
        $sampleHeaders = [];
        $foundSampleTable = false;

        foreach ($tableRows as $row) {
            $rowData = $this->extractRowCells($row);
            if (empty($rowData)) continue;
            $rowText = implode(' ', $rowData);

            if (!$foundSampleTable && SampleDataExtractor::isSampleTableHeader($rowText)) {
                $sampleHeaders = SampleDataExtractor::normalizeHeaders($rowData);
                $foundSampleTable = true; continue;
            }

            if ($foundSampleTable && count($rowData) >= count($sampleHeaders)) {
                $sampleEntry = SampleDataExtractor::createSampleEntry($rowData, $sampleHeaders);
                if (!empty($sampleEntry)) $sampleData[] = $sampleEntry;
                continue;
            }

            if (count($rowData) >= 2 && !empty($rowData[0])) {
                ActlabsFieldExtractor::extractCommonField($rowData[0], $rowData[1], $fields);
            }
        }
    }

    private function extractRowCells($row): array
    {
        $rowData = [];
        foreach ($row->getCells() as $cell) {
            $cellText = '';
            foreach ($cell->getElements() as $cellElement) { $cellText .= $this->extractTextFromElement($cellElement); }
            $rowData[] = trim($cellText);
        }
        return $rowData;
    }

    private function extractTextFromElement($element): string
    {
        if (method_exists($element, 'getText')) return $element->getText();
        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $inner) {
                if (method_exists($inner, 'getText')) $text .= $inner->getText();
            }
            return $text;
        }
        return '';
    }

    private function processTextElement($element, array &$textBlocks): void
    {
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                if (method_exists($child, 'getText')) {
                    $t = trim($child->getText()); if ($t !== '') $textBlocks[] = $t;
                }
            }
        } elseif (method_exists($element, 'getText')) {
            $t = trim($element->getText()); if ($t !== '') $textBlocks[] = $t;
        }
    }
}

// ---------------------------------------------
// Spreadsheet
// ---------------------------------------------
class SpreadsheetParser extends BaseDocumentParser
{
    public function parse(string $filePath, bool $isMain = true): array
    {
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            if (method_exists($reader, 'setReadDataOnly')) { $reader->setReadDataOnly(true); }
            $spreadsheet = $reader->load($filePath);

            $fields = $this->initializeFields();
            $sampleData = [];
            $textRows = [];

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $this->processWorksheet($worksheet, $fields, $sampleData, $textRows);
            }

            $text = implode("\n", $textRows);
            $fields = array_replace($fields, ActlabsFieldExtractor::extract($text));
            ActlabsFieldExtractor::sweepKeyValues($text, $fields);
            if (empty($sampleData)) { $sampleData = SampleDataExtractor::extract($text); }

            return [ 'fields' => $fields, 'raw' => $text, 'sample_data' => $sampleData ];
        } catch (\Throwable $e) {
            return [ 'fields' => $this->initializeFields(), 'raw' => 'Error parsing spreadsheet: ' . $e->getMessage(), 'sample_data' => [] ];
        }
    }

    private function processWorksheet($ws, array &$fields, array &$sampleData, array &$textRows): void
    {
        $maxRow = $ws->getHighestRow();
        $maxColLetter = $ws->getHighestColumn();
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxColLetter);

        // Try to locate header row by scanning for any header token in a dense row
        $headerMap = $this->findHeaderMap($ws, $maxRow, $maxCol);
        if (!empty($headerMap)) {
            for ($r = $headerMap['row'] + 1; $r <= $maxRow; $r++) {
                $entry = [];
                $hasData = false;
                foreach ($headerMap['cols'] as $colIndex => $header) {
                    $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $r;
                    $cell = $ws->getCell($coord);
                    $val = $cell->getFormattedValue();
                    if ($val !== null && $val !== '') { $hasData = true; }
                    $entry[$header] = $val;
                }
                if ($hasData) { $sampleData[] = $entry; } else { break; }
            }
        }

        // All text content (for secondary regex extraction fallback)
        for ($r = 1; $r <= $maxRow; $r++) {
            $rowParts = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $val = $ws->getCell($coord)->getFormattedValue();
                if ($val !== null && $val !== '') $rowParts[] = $val;
            }
            if (!empty($rowParts)) $textRows[] = implode(' | ', $rowParts);
        }

        // KV blocks (2-column label/value areas) - FIXED for PhpSpreadsheet 2.0+
        for ($r = 1; $r <= $maxRow; $r++) {
            for ($c = 1; $c < $maxCol; $c++) {
                $labelCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $valueCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . $r;
                $label = $ws->getCell($labelCoord)->getFormattedValue();
                $value = $ws->getCell($valueCoord)->getFormattedValue();
                if (is_string($label) && $label !== '' && $value !== '') {
                    ActlabsFieldExtractor::extractCommonField($label, $value, $fields);
                }
            }
        }
    }

    private function findHeaderMap($ws, int $maxRow, int $maxCol): array
    {
        for ($r = 1; $r <= min($maxRow, 100); $r++) {
            $hitCount = 0; $cols = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $val = $ws->getCell($coord)->getFormattedValue();
                if (is_string($val) && SampleDataExtractor::isSampleTableHeader($val)) {
                    $hitCount++;
                }
            }
            if ($hitCount >= 2) {
                for ($c = 1; $c <= $maxCol; $c++) {
                    $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                    $v = $ws->getCell($coord)->getFormattedValue();
                    if ($v !== null && $v !== '') {
                        $cols[$c] = SampleDataExtractor::normalizeHeader($v);
                    }
                }
                if (!empty($cols)) return ['row' => $r, 'cols' => $cols];
            }
        }
        return [];
    }
}

// ---------------------------------------------
// Field extraction helpers
// ---------------------------------------------
class ActlabsFieldExtractor
{
    public static function extract(string $content): array
    {
        $fields = [];
        foreach (ActlabsFormConfig::FIELD_PATTERNS as $fieldName => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $m)) { $fields[$fieldName] = trim($m[1] ?? $m[2] ?? ''); break; }
            }
        }
        self::extractPriority($content, $fields);
        self::extractConfirmation($content, $fields);
        self::extractPaymentMethod($content, $fields);
        return $fields;
    }

    public static function sweepKeyValues(string $content, array &$fields): void
    {
        $pattern = '/^(?P<key>[A-Za-z][A-Za-z \\/#&.()+-]{2,}):\\s*(?P<val>.+)$/m';
        if (preg_match_all($pattern, $content, $all, PREG_SET_ORDER)) {
            foreach ($all as $hit) {
                $label = trim($hit['key']); $value = trim($hit['val']);
                if ($label === '' || $value === '') continue;
                self::extractCommonField($label, $value, $fields);
            }
        }
    }

    public static function extractCommonField(string $label, string $value, array &$fields): void
    {
        $label = trim($label); $value = trim($value);
        if ($label === '' || $value === '') return;

        $map = [
            '/^Carrier$/i' => 'Carrier',
            '/^Waybill\s*#?$/i' => 'Waybill #',
            '/^(?:#|Number)\s*of\s*Packages$/i' => '# of Packages',
            '/^(?:#|Number)\s*of\s*Samples$/i' => '# of Samples',
            '/^Priority$/i' => 'Priority',
            '/^Confirmation\s+of\s+Sample\s+Receipt$/i' => 'Confirmation of Sample Receipt',
            '/^Sample\s+Receipt\s+Confirmation$/i' => 'Confirmation of Sample Receipt',
            '/^Special\s+Instructions?$/i' => 'Special Instructions/Comments',
            '/^Comments?$/i' => 'Special Instructions/Comments',
            '/^Client\s+Name$/i' => 'Client Name',
            '/^Client$/i' => 'Client Name',
            '/^Client\s+Batch\s*#?$/i' => 'Client Batch #',
            '/^Batch\s*#?$/i' => 'Client Batch #',
            '/^Shipment\s*#?$/i' => 'Shipment #',
            '/^Quote\s*#?.*$/i' => 'Quote #, PO #, Proforma #',
            '/^PO\s*#?$/i' => 'Quote #, PO #, Proforma #',
            '/^Proforma\s*#?$/i' => 'Quote #, PO #, Proforma #',
            '/^Project\s*(Name)?$/i' => 'Project',
            '/^Company\s*(Name)?$/i' => 'Company',
            '/^Address$/i' => 'Address',
            '/^Mailing\s+Address$/i' => 'Address',
            '/^Attn\.?$/i' => 'Attn',
            '/^Attention$/i' => 'Attn',
            '/^Phone$/i' => 'Phone',
            '/^Tel\.?$/i' => 'Phone',
            '/^Telephone$/i' => 'Phone',
            '/^Fax$/i' => 'Fax',
            '/^E-?mail$/i' => 'E-mail',
            '/^Email$/i' => 'E-mail',
            '/^Method\s+of\s+Payment$/i' => 'Payment Method',
            '/^Payment\s+Method$/i' => 'Payment Method',
            '/^Method\s+of\s+Sample\s+Return$/i' => 'Method of Sample Return',
            '/^Sample\s+Return$/i' => 'Method of Sample Return',
            '/^Additional\s+Report\s+(to|To)$/i' => 'Additional Report To',
            '/^Prep\.?\s*Code$/i' => 'Prep Code',
            '/^(Analysis\s*Code|Elements)$/i' => 'Analysis Code / Elements',
            '/^Credit\s+Card$/i' => 'Credit Card Info',
            '/^Reporting.*Invoicing$/i' => 'Reporting & Invoicing Preferences',
        ];
        
        foreach ($map as $pat => $name) {
            if (preg_match($pat, $label)) { 
                // Don't overwrite if already has a value, unless new value is longer
                if (!isset($fields[$name]) || $fields[$name] === '' || strlen($value) > strlen($fields[$name])) {
                    $fields[$name] = $value; 
                }
                return; 
            }
        }

        // Special handling for priority
        if (preg_match('/^Priority$/i', $label)) { 
            if (preg_match('/RUSH|URGENT|EMERGENCY|ASAP/i', $value)) {
                $fields['Priority'] = 'RUSH';
            } else if (preg_match('/NORMAL|STANDARD|ROUTINE/i', $value)) {
                $fields['Priority'] = 'Normal';
            } else {
                $fields['Priority'] = $value;
            }
            return; 
        }
        
        // Special handling for confirmation
        if (preg_match('/^Confirmation\s+of\s+Sample\s+Receipt$/i', $label)) {
            if (preg_match('/\\bYes\\b/i', $value)) $fields['Confirmation of Sample Receipt'] = 'Yes';
            elseif (preg_match('/\\bNo\\b/i', $value)) $fields['Confirmation of Sample Receipt'] = 'No';
            else $fields['Confirmation of Sample Receipt'] = $value; 
            return;
        }

        // Store unmatched fields with normalized label
        $normalizedLabel = self::normalizeFieldLabel($label);
        if (!isset($fields[$normalizedLabel]) || $fields[$normalizedLabel] === '') {
            $fields[$normalizedLabel] = $value;
        }
    }

    private static function extractPriority(string $content, array &$fields): void
    {
        // Check for explicit Priority field first
        if (isset($fields['Priority']) && $fields['Priority'] !== '') {
            // Already set, normalize it
            if (preg_match('/RUSH|URGENT|EMERGENCY|ASAP/i', $fields['Priority'])) {
                $fields['Priority'] = 'RUSH';
            } else if (preg_match('/NORMAL|STANDARD|ROUTINE/i', $fields['Priority'])) {
                $fields['Priority'] = 'Normal';
            }
            return;
        }
        
        // Look for priority indicators in content
        if (preg_match('/Priority\s*[:\-]?\s*(RUSH|URGENT|EMERGENCY|ASAP)/i', $content, $m)) {
            $fields['Priority'] = 'RUSH';
        } else if (preg_match('/Priority\s*[:\-]?\s*(NORMAL|STANDARD|ROUTINE)/i', $content, $m)) {
            $fields['Priority'] = 'Normal';
        } else if (preg_match('/\b(RUSH|URGENT|EMERGENCY)\b/i', $content)) {
            // Only mark as RUSH if we find these keywords standalone
            $fields['Priority'] = 'RUSH';
        } else {
            // Default to Normal if not specified
            $fields['Priority'] = 'Normal';
        }
    }

    private static function extractConfirmation(string $content, array &$fields): void
    {
        if (!isset($fields['Confirmation of Sample Receipt']) && preg_match('/Confirmation\\s+of\\s+Sample\\s+Receipt/i', $content)) {
            if (preg_match('/\\bYes\\b/i', $content)) $fields['Confirmation of Sample Receipt'] = 'Yes';
            elseif (preg_match('/\\bNo\\b/i', $content)) $fields['Confirmation of Sample Receipt'] = 'No';
        }
    }

    private static function extractPaymentMethod(string $content, array &$fields): void
    {
        foreach (ActlabsFormConfig::PAYMENT_PATTERNS as $method => $pattern) {
            if (preg_match($pattern, $content)) { $fields['Payment Method'] = $method; break; }
        }
    }

    private static function normalizeFieldLabel(string $label): string
    { $label = preg_replace('/\\s+/', ' ', trim($label)); return ucwords(strtolower($label)); }
}

// ---------------------------------------------
// Sample table extraction
// ---------------------------------------------
class SampleDataExtractor
{
    public static function extract(string $content): array
    {
        $lines = preg_split('/\\r?\\n/', $content);
        $sampleData = []; $in = false; $headers = [];
        foreach ($lines as $line) {
            if (!$in && self::isSampleTableHeader($line)) { $headers = self::extractHeaders($line); $in = true; continue; }
            if ($in && !empty($headers)) {
                if (self::isSampleTableHeader($line)) { // handle repeated header rows in PDFs
                    $headers = self::extractHeaders($line); continue;
                }
                $row = self::extractRowData($line, $headers);
                if (!empty($row)) $sampleData[] = $row;
            }
        }
        return $sampleData;
    }

    public static function isSampleTableHeader(string $line): bool
    { return (bool) preg_match('/(Sample\\s+Numbers?|Sample\\s+ID|Sample\\s+Type|Prep\\.?\\s*Code|Analysis\\s*Code|Elements|Assay)/i', $line); }

    public static function normalizeHeaders(array $headers): array
    { return array_map([self::class, 'normalizeHeader'], $headers); }

    public static function normalizeHeader(string $header): string
    {
        $header = trim($header);
        foreach (ActlabsFormConfig::SAMPLE_HEADER_MAPPINGS as $pattern => $normalized) {
            if (preg_match($pattern, $header)) return $normalized;
        }
        return $header;
    }

    public static function createSampleEntry(array $rowData, array $headers): array
    {
        $entry = [];
        for ($i = 0; $i < count($headers); $i++) { $entry[$headers[$i]] = $rowData[$i] ?? ''; }
        return array_filter($entry, fn($v) => $v !== null && $v !== '');
    }

    private static function extractHeaders(string $line): array
    {
        $parts = preg_split('/\\s{2,}|\\t|\\s\\|\\s/', $line);
        return array_map([self::class, 'normalizeHeader'], array_filter($parts, 'trim'));
    }

    private static function extractRowData(string $line, array $headers): array
    {
        if (trim($line) === '' || self::isNonDataRow($line)) return [];
        $parts = preg_split('/\\s{2,}|\\t|\\s\\|\\s/', $line);
        $vals = array_values(array_filter($parts, 'trim'));
        if (count($vals) < 2) return [];
        $entry = [];
        foreach ($headers as $j => $h) { $entry[$h] = $vals[$j] ?? ''; }
        return $entry;
    }

    private static function isNonDataRow(string $line): bool
    { foreach (ActlabsFormConfig::SKIP_PATTERNS as $p) { if (preg_match($p, $line)) return true; } return false; }

    // ---- Counts helpers (used by Processor and Blade-wide summary) ----
    public static function summarizeCodes(array $rows): array
    {
        $prepCounts = [];
        $analysisCounts = [];
        foreach ($rows as $row) {
            if (!empty($row['Prep Code'])) {
                $prep = trim($row['Prep Code']);
                if ($prep !== '') $prepCounts[$prep] = ($prepCounts[$prep] ?? 0) + 1;
            }
            if (!empty($row['Analysis Code / Elements'])) {
                $codes = preg_split('/[,;\\/]+/', $row['Analysis Code / Elements']);
                foreach ($codes as $c) {
                    $c = trim($c); if ($c === '') continue;
                    $analysisCounts[$c] = ($analysisCounts[$c] ?? 0) + 1;
                }
            }
        }
        ksort($prepCounts); ksort($analysisCounts);
        return [
            'Prep Code Counts' => $prepCounts,
            'Analysis Code Counts' => $analysisCounts
        ];
    }
}

// ---------------------------------------------
// Final processing
// ---------------------------------------------
class ActlabsFormProcessor
{
    public function process(array $parsed): array
    {
        $fields = $parsed['fields'] ?? [];
        $sampleData = $parsed['sample_data'] ?? [];

        $fields = $this->ensureRequiredFields($fields);
        $fields = $this->orderFields($fields);
        if (!empty($sampleData)) {
            $fields['Sample Data'] = $sampleData;
            $summ = SampleDataExtractor::summarizeCodes($sampleData);
            if (!empty($summ['Prep Code Counts'])) $fields['Prep Code Counts'] = $summ['Prep Code Counts'];
            if (!empty($summ['Analysis Code Counts'])) $fields['Analysis Code Counts'] = $summ['Analysis Code Counts'];
        }

        return [ 'fields' => $fields, 'raw' => $parsed['raw'] ?? '', 'sample_data' => $sampleData ];
    }

    private function ensureRequiredFields(array $fields): array
    { foreach (ActlabsFormConfig::REQUIRED_FIELDS as $f) { if (!array_key_exists($f, $fields)) $fields[$f] = ''; } return $fields; }

    private function orderFields(array $fields): array
    {
        $ordered = [];
        foreach (ActlabsFormConfig::REQUIRED_FIELDS as $f) { if (array_key_exists($f, $fields)) { $ordered[$f] = $fields[$f]; unset($fields[$f]); } }
        return $ordered + $fields;
    }
}

// ---------------------------------------------
// HTML helpers (preview + counts + CSV fallback)
// ---------------------------------------------
class HtmlPreview
{
    public static function tableFromSampleData(array $rows): string
    {
        if (empty($rows)) return '';
        $headers = array_unique(array_merge(...array_map(fn($r) => array_keys($r), $rows)));
        $out = '<table class="sample-table">';
        $out .= '<thead><tr>' . implode('', array_map(fn($h) => '<th>' . e($h) . '</th>', $headers)) . '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $out .= '<tr>' . implode('', array_map(fn($h) => '<td>' . e((string)($r[$h] ?? '')) . '</td>', $headers)) . '</tr>';
        }
        $out .= '</tbody></table>';
        return $out;
    }

    public static function tableFromSpreadsheet(string $fullPath): string
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
        if (method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
        $ss = $reader->load($fullPath);
        $ws = $ss->getSheet(0);
        $maxRow = $ws->getHighestRow();
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($ws->getHighestColumn());

        // Trim trailing empties
        while ($maxRow > 1) {
            $nonEmpty = false;
            for ($c = 1; $c <= $maxCol; $c++) { 
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $maxRow;
                if ((string)$ws->getCell($coord)->getFormattedValue() !== '') { $nonEmpty = true; break; } 
            }
            if ($nonEmpty) break; $maxRow--;
        }
        while ($maxCol > 1) {
            $nonEmpty = false;
            for ($r = 1; $r <= $maxRow; $r++) { 
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxCol) . $r;
                if ((string)$ws->getCell($coord)->getFormattedValue() !== '') { $nonEmpty = true; break; } 
            }
            if ($nonEmpty) break; $maxCol--;
        }

        $out = '<table class="sample-table">';
        // Thead detection
        $firstRowVals = [];
        for ($c = 1; $c <= $maxCol; $c++) { 
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . '1';
            $firstRowVals[] = (string)$ws->getCell($coord)->getFormattedValue(); 
        }
        $looksLikeHeader = 0;
        foreach ($firstRowVals as $v) { if (preg_match('/(Sample|Prep|Analysis|Element|Code|Type)/i', $v)) $looksLikeHeader++; }
        if ($looksLikeHeader >= 2) {
            $out .= '<thead><tr>';
            for ($c = 1; $c <= $maxCol; $c++) { 
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . '1';
                $out .= '<th>' . e((string)$ws->getCell($coord)->getFormattedValue()) . '</th>'; 
            }
            $out .= '</tr></thead><tbody>';
            $rowStart = 2;
        } else {
            $out .= '<tbody>';
            $rowStart = 1;
        }
        for ($r = $rowStart; $r <= $maxRow; $r++) {
            $out .= '<tr>';
            for ($c = 1; $c <= $maxCol; $c++) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                $val = $ws->getCell($coord)->getFormattedValue();
                $out .= '<td>' . e((string)$val) . '</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';
        return $out;
    }

    public static function toTempCsv(string $fullPath): string
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
        if (method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(true);
        $ss = $reader->load($fullPath);
        $tmp = tempnam(sys_get_temp_dir(), 'xlscsv');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($ss);
        $writer->save($tmp);
        return $tmp;
    }

    public static function summarizeCounts(array $samplesLists): array
    {
        $prep = [];$analysis = [];
        foreach ($samplesLists as $rows) {
            foreach ($rows as $r) {
                if (!empty($r['Prep Code'])) {
                    $k = trim($r['Prep Code']); if ($k!=='') $prep[$k] = ($prep[$k] ?? 0) + 1;
                }
                if (!empty($r['Analysis Code / Elements'])) {
                    $codes = preg_split('/[,;\\/]+/', $r['Analysis Code / Elements']);
                    foreach ($codes as $c) { $c = trim($c); if ($c!=='') $analysis[$c] = ($analysis[$c] ?? 0) + 1; }
                }
            }
        }
        ksort($prep); ksort($analysis);
        return ['prep' => $prep, 'analysis' => $analysis];
    }
}
