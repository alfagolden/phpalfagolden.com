<?php
/**
 * Word Template Processor
 * Processes Word templates with placeholder replacement
 */

// Configuration
$base_template_url = "https://files.alfagolden.com/files/main/public/system/docs/";
$base_template_local_path = "/var/www/files/main/public/system/docs/";
$output_dir = "/var/www/files/main/public/system/docs";
$base_http = "https://alfagolden.com/system/docs";

// Template mapping based on brand
$template_map = [
    'ALFA PRO' => 'PBP.docx',
    'FUJI' => 'PBU.docx',
    'MITSUTECH' => 'GT2.docx',
    'ALFA ELITE' => 'SVZ.docx'
];

// API Configuration
$API_CONFIG = [
    'baseUrl' => 'https://base.alfagolden.com',
    'token' => 'h5qAt85gtiJDAzpH51WrXPywhmnhrPWy',
    'quotesTableId' => 704
];

/**
 * Get template paths based on brand
 * @param array $quote_data The quote data containing field_6973
 * @return array Array containing template_url and template_local_path
 * @throws Exception If brand is not found or invalid
 */
function getTemplatePaths($quote_data) {
    global $base_template_url, $base_template_local_path, $template_map;

    // Extract brand from field_6973
    $brand = getFieldValue($quote_data, 6973, '');

    if (empty($brand)) {
        throw new Exception("No brand specified in field_6973");
    }

    if (!isset($template_map[$brand])) {
        throw new Exception("Invalid or unsupported brand: " . $brand);
    }

    $template_filename = $template_map[$brand];
    $template_url = $base_template_url . $template_filename;
    $template_local_path = $base_template_local_path . $template_filename;

    return [
        'template_url' => $template_url,
        'template_local_path' => $template_local_path
    ];
}

/**
 * Refresh both Word and PDF files for a given quote ID
 * Deletes existing files and regenerates new ones
 *
 * @param int $quote_id The ID of the quote to process
 * @return array Array containing success status and file information
 */
function Refresh_pdf_word($quote_id) {
    global $output_dir, $base_http;

    try {
        // Validate quote ID
        if ($quote_id <= 0) {
            throw new Exception("Invalid quote ID: " . $quote_id);
        }
        // Log the refresh attempt
        error_log("Starting refresh for quote ID: " . $quote_id);
        // Delete existing Word file if it exists
        $word_filename = $quote_id . '.docx';
        $word_path = $output_dir . '/' . $word_filename;
        if (file_exists($word_path)) {
            if (!unlink($word_path)) {
                throw new Exception("Failed to delete existing Word file: " . $word_path);
            }
            error_log("Deleted existing Word file: " . $word_path);
        }
        // Delete existing PDF file if it exists
        $pdf_filename = $quote_id . '.pdf';
        $pdf_path = $output_dir . '/' . $pdf_filename;
        if (file_exists($pdf_path)) {
            if (!unlink($pdf_path)) {
                throw new Exception("Failed to delete existing PDF file: " . $pdf_path);
            }
            error_log("Deleted existing PDF file: " . $pdf_path);
        }
        // Generate new Word document
        $word_result = generateWordDocument($quote_id);
        if (!$word_result['success']) {
            throw new Exception("Failed to generate new Word document: " . $word_result['error']);
        }
        error_log("New Word document generated successfully: " . $word_result['file_path']);
        // Generate new PDF document
        $pdf_result = generatePDFFromTemplate($quote_id);
        if (!$pdf_result['success']) {
            throw new Exception("Failed to generate new PDF document: " . $pdf_result['error']);
        }
        error_log("New PDF document generated successfully: " . $pdf_result['file_path']);
        // Get file information
        $file_info = getFileInfo($quote_id);
        return [
            'success' => true,
            'message' => 'Word and PDF files refreshed successfully',
            'files' => $file_info
        ];
    } catch (Exception $e) {
        error_log("Refresh failed for quote ID: " . $quote_id . " - Error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Download template file if not exists locally
 */
function ensureTemplateExists($template_url, $template_local_path) {
    if (!file_exists($template_local_path)) {
        // Use cURL for better error handling
        $ch = curl_init($template_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        $template_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($template_content === false || $http_code !== 200) {
            throw new Exception("Cannot download template file from: " . $template_url . " (HTTP: " . $http_code . ", cURL Error: " . $curl_error . ")");
        }

        $dir = dirname($template_local_path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                throw new Exception("Cannot create directory: " . $dir);
            }
        }

        if (file_put_contents($template_local_path, $template_content) === false) {
            throw new Exception("Cannot save template file to: " . $template_local_path);
        }
    }
    return true;
}

/**
 * Fetch quote data from API
 */
function fetchQuoteData($quote_id) {
    global $API_CONFIG;

    $url = $API_CONFIG['baseUrl'] . '/api/database/rows/table/' . $API_CONFIG['quotesTableId'] . '/' . $quote_id . '/?user_field_names=false';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Token h5qAt85gtiJDAzpH51WrXPywhmnhrPWy"],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to fetch quote data. HTTP Code: " . $httpCode);
    }

    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception("Invalid JSON response from API");
    }

    return $data;
}

/**
 * Extract value from field array
 */
function extractValue($field, $default = '') {
    if (is_array($field) && !empty($field) && isset($field[0]['value'])) {
        return is_array($field[0]['value']) ? ($field[0]['value']['value'] ?? $default) : $field[0]['value'];
    }
    return $field ?: $default;
}

/**
 * Extract value from field data based on field number
 */
function getFieldValue($data, $field_number, $default = '') {
    $field_key = 'field_' . $field_number;

    if (!isset($data[$field_key])) {
        return $default;
    }

    $field_data = $data[$field_key];

    // If it's null or empty
    if ($field_data === null || $field_data === '') {
        return $default;
    }

    // If it's a direct string value
    if (is_string($field_data)) {
        return $field_data;
    }

    // If it's an array, get the first item's value
    if (is_array($field_data) && !empty($field_data)) {
        $first_item = $field_data[0];

        // If the first item has a 'value' key
        if (isset($first_item['value'])) {
            $value = $first_item['value'];

            // If the value is an object/array with nested value
            if (is_array($value) && isset($value['value'])) {
                return $value['value'];
            }

            return $value;
        }
    }

    return $default;
}

/**
 * Replace numeric placeholders in text with field values
 */
function replaceNumericPlaceholders($text, $data) {
    // Pattern to match numbers (field IDs) in the text - more specific to avoid replacing random numbers
    $pattern = '/\b(\d{4,})\b/';

    // Count how many replacements we're making to prevent infinite loops
    $replacement_count = 0;
    $max_replacements = 100; // Safety limit

    return preg_replace_callback($pattern, function($matches) use ($data, &$replacement_count, $max_replacements) {
        if ($replacement_count >= $max_replacements) {
            error_log("Too many replacements, stopping to prevent infinite loop");
            return $matches[0]; // Return original number
        }

        $field_number = $matches[1];
        $value = getFieldValue($data, $field_number);

        // If no value found, return original number
        if ($value === '' || $value === null) {
            return $matches[0];
        }

        $replacement_count++;

        // Format the value if it's a price
        if (is_numeric($value) && $value > 1000) {
            return formatArabicPrice($value);
        }

        return $value;
    }, $text);
}

/**
 * Format date to Arabic format
 */
function formatArabicDate($dateStr) {
    if (!$dateStr) return '';

    $date = new DateTime($dateStr);
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    ];

    $day = $date->format('j');
    $month = $months[(int)$date->format('n')];
    $year = $date->format('Y');

    return $day . ' ' . $month . ' ' . $year;
}

/**
 * Format price with Arabic numbers
 */
function formatArabicPrice($price) {
    if (!$price) return '0';

    $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $formatted = number_format(round($price));
    return str_replace($englishNumbers, $arabicNumbers, $formatted);
}

/**
 * Check if Word file exists for a quote ID
 */
function checkWordFileExists($quote_id) {
    global $output_dir;
    $word_filename = $quote_id . '.docx';
    $word_path = $output_dir . '/' . $word_filename;
    return file_exists($word_path);
}

/**
 * Check if PDF file exists for a quote ID
 */
function checkPDFFileExists($quote_id) {
    global $output_dir;
    $pdf_filename = $quote_id . '.pdf';
    $pdf_path = $output_dir . '/' . $pdf_filename;
    return file_exists($pdf_path);
}

/**
 * Get file information for a quote ID
 */
function getFileInfo($quote_id) {
    global $output_dir, $base_http;

    $word_filename = $quote_id . '.docx';
    $pdf_filename = $quote_id . '.pdf';
    $word_path = $output_dir . '/' . $word_filename;
    $pdf_path = $output_dir . '/' . $pdf_filename;

    $info = [
        'quote_id' => $quote_id,
        'word' => [
            'exists' => file_exists($word_path),
            'filename' => $word_filename,
            'path' => $word_path,
            'url' => $base_http . '/' . $word_filename,
            'size' => file_exists($word_path) ? filesize($word_path) : 0,
            'modified' => file_exists($word_path) ? date('Y-m-d H:i:s', filemtime($word_path)) : null
        ],
        'pdf' => [
            'exists' => file_exists($pdf_path),
            'filename' => $pdf_filename,
            'path' => $pdf_path,
            'url' => $base_http . '/' . $pdf_filename,
            'size' => file_exists($pdf_path) ? filesize($pdf_path) : 0,
            'modified' => file_exists($pdf_path) ? date('Y-m-d H:i:s', filemtime($pdf_path)) : null
        ]
    ];

    return $info;
}

/**
 * Convert existing Word file to PDF (without regenerating Word)
 */
function convertExistingWordToPDF($quote_id) {
    global $output_dir, $base_http;

    try {
        // Check if Word file exists
        $word_filename = $quote_id . '.docx';
        $word_path = $output_dir . '/' . $word_filename;

        if (!file_exists($word_path)) {
            throw new Exception("Word file not found: " . $word_path . ". Please generate the Word file first.");
        }

        // Prepare output PDF path
        $output_pdf_filename = $quote_id . '.pdf';
        $output_pdf_path = $output_dir . '/' . $output_pdf_filename;

        // Try different conversion methods
        $conversion_success = false;

        // Method 1: Try LibreOffice (if available)
        $libreoffice_path = '/usr/bin/libreoffice';
        if (file_exists($libreoffice_path)) {
            $command = escapeshellcmd($libreoffice_path) .
                       ' --headless --convert-to pdf --outdir ' .
                       escapeshellarg($output_dir) . ' ' .
                       escapeshellarg($word_path) . ' 2>&1';

            exec($command, $output, $return_var);

            // LibreOffice creates file with same name but .pdf extension
            $temp_pdf = $output_dir . '/' . $quote_id . '.pdf';
            if (file_exists($temp_pdf)) {
                $conversion_success = true;
            }
        }

        // Method 2: Try wkhtmltopdf (if available)
        if (!$conversion_success) {
            $wkhtmltopdf_path = '/usr/bin/wkhtmltopdf';
            if (file_exists($wkhtmltopdf_path)) {
                // Convert Word to HTML first using pandoc (if available)
                $pandoc_path = '/usr/bin/pandoc';
                if (file_exists($pandoc_path)) {
                    $temp_html = $output_dir . '/' . $quote_id . '_temp.html';
                    $pandoc_command = escapeshellcmd($pandoc_path) .
                                     ' -f docx -t html ' .
                                     escapeshellarg($word_path) . ' -o ' .
                                     escapeshellarg($temp_html) . ' 2>&1';

                    exec($pandoc_command, $pandoc_output, $pandoc_return);

                    if (file_exists($temp_html)) {
                        // Convert HTML to PDF
                        $wkhtml_command = escapeshellcmd($wkhtmltopdf_path) .
                                         ' --encoding UTF-8 --enable-local-file-access ' .
                                         escapeshellarg($temp_html) . ' ' .
                                         escapeshellarg($output_pdf_path) . ' 2>&1';

                        exec($wkhtml_command, $wkhtml_output, $wkhtml_return);

                        // Clean up temp HTML
                        unlink($temp_html);

                        if (file_exists($output_pdf_path)) {
                            $conversion_success = true;
                        }
                    }
                }
            }
        }

        // Method 3: Try ONLYOFFICE conversion API
        if (!$conversion_success) {
            $conversion_success = convertWithONLYOFFICE($word_path, $output_pdf_path);
        }

        if (!$conversion_success) {
            throw new Exception("Failed to convert Word to PDF. Please check ONLYOFFICE server connection and configuration.");
        }

        // Set proper permissions
        chmod($output_pdf_path, 0664);

        return [
            'success' => true,
            'file_path' => $output_pdf_path,
            'file_url' => $base_http . '/' . $output_pdf_filename,
            'filename' => $output_pdf_filename,
            'source_word_file' => $word_path
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process Word template with data replacement (including headers and footers)
 */
function processWordTemplate($template_path, $output_path, $data) {
    if (!class_exists('ZipArchive')) {
        throw new Exception("ZipArchive class not available. Please install php-zip extension.");
    }

    // Open template as ZIP
    $zip = new ZipArchive();
    if ($zip->open($template_path) !== TRUE) {
        throw new Exception("Cannot open template file: " . $template_path);
    }

    // Log the API data for debugging
    error_log("API Response Data: " . json_encode($data, JSON_UNESCAPED_UNICODE));

    // Collect all placeholders from all relevant XML files to build replacements
    $all_placeholders = [];
    $xml_files = [];

    // Scan all XML files in word/ directory
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (strpos($filename, 'word/') === 0 && substr($filename, -4) === '.xml') {
            $content = $zip->getFromName($filename);
            if ($content !== false) {
                $xml_files[$filename] = $content;
                // Find placeholders like {{1234}}
                preg_match_all('/\{\{([0-9]+)\}\}/', $content, $matches);
                if (!empty($matches[1])) {
                    $all_placeholders = array_merge($all_placeholders, $matches[1]);
                }
            }
        }
    }

    $all_placeholders = array_unique($all_placeholders);

    // Log found placeholders
    error_log("Found Placeholders in all XML files: " . json_encode($all_placeholders, JSON_UNESCAPED_UNICODE));

    if (empty($all_placeholders)) {
        error_log("Warning: No placeholders found in any XML file. Check the Word template for correct placeholder format (e.g., {{6977}})");
    }

    // Build replacements array
    $replacements = [];
    foreach ($all_placeholders as $field_id) {
        $field_key = 'field_' . $field_id;
        if (!isset($data[$field_key])) {
            error_log("Warning: Field $field_key not found in API response");
            $value = '';
        } else {
            $value = extractValue($data[$field_key]);
            error_log("Raw value for $field_key: " . json_encode($data[$field_key], JSON_UNESCAPED_UNICODE));
            error_log("Extracted value for {{$field_id}}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        // Apply special formatting
        if (in_array($field_id, ['6983', '6984', '6995', '6996', '6997'])) {
            $value = formatArabicPrice($value);
            error_log("Applied formatArabicPrice for {{$field_id}}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
        } elseif (in_array($field_id, ['6789', '7035'])) {
            $value = formatArabicDate($value);
            error_log("Applied formatArabicDate for {{$field_id}}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        $replacements['{{' . $field_id . '}}'] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Create new ZIP output
    $new_zip = new ZipArchive();
    if ($new_zip->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        $zip->close();
        throw new Exception("Cannot create output file: " . $output_path);
    }

    // Process and add all files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        $content = $zip->getFromIndex($i);

        if ($content === false) {
            $new_zip->addFromString($filename, ''); // or skip?
            continue;
        }

        // If it's an XML file in word/ folder, apply replacements
        if (strpos($filename, 'word/') === 0 && substr($filename, -4) === '.xml') {
            $processed_content = $content;
            foreach ($replacements as $placeholder => $value) {
                $processed_content = str_replace($placeholder, $value, $processed_content);
            }
            $new_zip->addFromString($filename, $processed_content);
        } else {
            // Copy other files (media, rels, etc.) as-is
            $new_zip->addFromString($filename, $content);
        }
    }

    $zip->close();
    $new_zip->close();

    // Verify output
    if (!file_exists($output_path) || filesize($output_path) == 0) {
        throw new Exception("Generated Word file is empty or not created: " . $output_path);
    }

    error_log("Word file generated successfully: " . $output_path . ", Size: " . filesize($output_path) . " bytes");
    return true;
}
/**
 * Main processing function
 */
function generateWordDocument($quote_id) {
    global $output_dir, $base_http;

    try {
        // Fetch quote data
        $quote_data = fetchQuoteData($quote_id);

        // Get template paths based on brand
        $template_paths = getTemplatePaths($quote_data);
        $template_url = $template_paths['template_url'];
        $template_local_path = $template_paths['template_local_path'];

        // Ensure template exists
        ensureTemplateExists($template_url, $template_local_path);

        // Prepare output path
        $output_filename = $quote_id . '.docx';
        $output_path = $output_dir . '/' . $output_filename;

        // Ensure output directory exists
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0775, true);
        }

        // Process template
        processWordTemplate($template_local_path, $output_path, $quote_data);

        // Set proper permissions
        chmod($output_path, 0664);

        return [
            'success' => true,
            'file_path' => $output_path,
            'file_url' => $base_http . '/' . $output_filename,
            'filename' => $output_filename
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate PDF from existing Word document
 */
function generatePDFFromTemplate($quote_id) {
    global $output_dir, $base_http;

    try {
        error_log("Starting PDF generation for quote ID: " . $quote_id);

        // Check if Word file already exists
        $word_filename = $quote_id . '.docx';
        $word_path = $output_dir . '/' . $word_filename;

        error_log("Checking for Word file: " . $word_path);
        error_log("Word file exists: " . (file_exists($word_path) ? 'YES' : 'NO'));

        // If Word file doesn't exist, generate it first
        if (!file_exists($word_path)) {
            error_log("Word file not found, generating new one...");
            $word_result = generateWordDocument($quote_id);
            if (!$word_result['success']) {
                throw new Exception("Failed to generate Word document: " . $word_result['error']);
            }
            error_log("Word document generated successfully");
        } else {
            error_log("Word file already exists, using existing file");
        }

        // Verify the Word file exists
        if (!file_exists($word_path)) {
            throw new Exception("Word file not found: " . $word_path);
        }

        error_log("Word file confirmed to exist, proceeding with PDF conversion");

        // Check Word file size to prevent huge PDFs
        $word_file_size = filesize($word_path);
        error_log("Word file size: " . $word_file_size . " bytes");

        if ($word_file_size > 10 * 1024 * 1024) { // 10MB limit
            error_log("Word file too large: " . $word_file_size . " bytes, this might cause issues");
        }

        // Prepare output PDF path
        $output_pdf_filename = $quote_id . '.pdf';
        $output_pdf_path = $output_dir . '/' . $output_pdf_filename;

        // Try different conversion methods
        $conversion_success = false;

        // Method 1: Try LibreOffice (if available)
        $libreoffice_path = '/usr/bin/libreoffice';
        if (file_exists($libreoffice_path)) {
            $command = escapeshellcmd($libreoffice_path) .
                       ' --headless --convert-to pdf --outdir ' .
                       escapeshellarg($output_dir) . ' ' .
                       escapeshellarg($word_path) . ' 2>&1';

            exec($command, $output, $return_var);

            // LibreOffice creates file with same name but .pdf extension
            $temp_pdf = $output_dir . '/' . $quote_id . '.pdf';
            if (file_exists($temp_pdf)) {
                $conversion_success = true;
            }
        }

        // Method 2: Try wkhtmltopdf (if available)
        if (!$conversion_success) {
            $wkhtmltopdf_path = '/usr/bin/wkhtmltopdf';
            if (file_exists($wkhtmltopdf_path)) {
                // Convert Word to HTML first using pandoc (if available)
                $pandoc_path = '/usr/bin/pandoc';
                if (file_exists($pandoc_path)) {
                    $temp_html = $output_dir . '/' . $quote_id . '_temp.html';
                    $pandoc_command = escapeshellcmd($pandoc_path) .
                                     ' -f docx -t html ' .
                                     escapeshellarg($word_path) . ' -o ' .
                                     escapeshellarg($temp_html) . ' 2>&1';

                    exec($pandoc_command, $pandoc_output, $pandoc_return);

                    if (file_exists($temp_html)) {
                        // Convert HTML to PDF
                        $wkhtml_command = escapeshellcmd($wkhtmltopdf_path) .
                                         ' --encoding UTF-8 --enable-local-file-access ' .
                                         escapeshellarg($temp_html) . ' ' .
                                         escapeshellarg($output_pdf_path) . ' 2>&1';

                        exec($wkhtml_command, $wkhtml_output, $wkhtml_return);

                        // Clean up temp HTML
                        unlink($temp_html);

                        if (file_exists($output_pdf_path)) {
                            $conversion_success = true;
                        }
                    }
                }
            }
        }

        // Method 3: Try ONLYOFFICE conversion API
        if (!$conversion_success) {
            $conversion_success = convertWithONLYOFFICE($word_path, $output_pdf_path);
        }

        if (!$conversion_success) {
            throw new Exception("Failed to convert Word to PDF. Please check ONLYOFFICE server connection and configuration.");
        }

        // Check PDF file size after conversion
        if (file_exists($output_pdf_path)) {
            $pdf_file_size = filesize($output_pdf_path);
            error_log("PDF file size: " . $pdf_file_size . " bytes");

            if ($pdf_file_size > 50 * 1024 * 1024) { // 50MB limit
                error_log("WARNING: PDF file is very large: " . $pdf_file_size . " bytes");
            }
        }

        // Set proper permissions
        chmod($output_pdf_path, 0664);

        return [
            'success' => true,
            'file_path' => $output_pdf_path,
            'file_url' => $base_http . '/' . $output_pdf_filename,
            'filename' => $output_pdf_filename
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Convert using ONLYOFFICE Document Conversion API
 */
function convertWithONLYOFFICE($input_path, $output_path) {
    global $base_http;

    $onlyoffice_server = 'https://office.alfagolden.com';
    $conversion_url = $onlyoffice_server . '/ConvertService.ashx';

    // Make the file accessible via HTTP
    $temp_filename = basename($input_path);
    $file_url = $base_http . '/' . $temp_filename;

    // Prepare conversion request with proper settings
    $data = [
        'async' => false,
        'filetype' => 'docx',
        'key' => md5($file_url . '_' . time() . '_' . rand()),
        'outputtype' => 'pdf',
        'title' => pathinfo($temp_filename, PATHINFO_FILENAME) . '.pdf',
        'url' => $file_url
    ];

    // Add JWT if available
    $jwt_secret = 'EzZhbzey1tTFYqbCllCIWQNC7RmDbLbZ';
    if (!empty($jwt_secret)) {
        $token = createJWT($data, $jwt_secret);
        $data['token'] = $token;
    }

    $ch = curl_init($conversion_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Debug log
    error_log("ONLYOFFICE Conversion Response: HTTP $http_code");
    error_log("Response: " . $response);

    if ($http_code !== 200 || !$response) {
        error_log("ONLYOFFICE Conversion Failed: $error");
        return false;
    }

    $result = json_decode($response, true);

    if (isset($result['fileUrl']) || isset($result['url'])) {
        $pdf_url = $result['fileUrl'] ?? $result['url'];
        $pdf_content = @file_get_contents($pdf_url);

        if ($pdf_content !== false && strlen($pdf_content) > 0) {
            file_put_contents($output_path, $pdf_content);
            return true;
        }
    }

    if (isset($result['error'])) {
        error_log("ONLYOFFICE Error: " . print_r($result['error'], true));
    }

    return false;
}

/**
 * Create JWT token for ONLYOFFICE
 */
function createJWT($payload, $secret) {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Test function to demonstrate numeric placeholder replacement
 */
function testNumericPlaceholders() {
    // Sample data from the API
    $test_data = [
        "id" => 61,
        "order" => "59.00000000000000000000",
        "field_6783" => "7061",
        "field_6784" => [["id" => 27, "value" => "السيد"]],
        "field_6785" => [["id" => 27, "value" => "المحترم"]],
        "field_6786" => [["id" => 27, "value" => "+966539167746", "order" => "17.00000000000000000000"]],
        "field_6788" => [["id" => 20, "value" => "منزل فلان الفلاني", "order" => "20.00000000000000000000"]],
        "field_6789" => "2025-08-14",
        "field_6794" => "1",
        "field_6796" => [["id" => 41, "value" => "داخل البئر في أعلاه"]],
        "field_6797" => [["id" => 41, "value" => "7"]],
        "field_6798" => [["id" => 41, "value" => "630"]],
        "field_6799" => [["id" => 41, "value" => "8"]],
        "field_6800" => "مدخلين",
        "field_6973" => [["id" => 41, "value" => ["id" => 3016, "value" => "ALFA ELITE", "color" => "darker-brown"]]],
        "field_6977" => [["id" => 27, "value" => "هشام بدر"]],
        "field_6983" => [["id" => 41, "value" => "64500"]],
        "field_6984" => "67000",
        "field_7138" => null,
        "field_7139" => null,
        "field_7140" => null,
        "field_7141" => null
    ];

    // Test text with numeric placeholders
    $test_text = "العميل: 6977 - المشروع: 6788 - الهاتف: 6786 - النوع: 6973 - السعر: 6983 - الإجمالي: 6984 - الماكينة: 6796 - الوقفات: 6797 - الحمولة: 6798 - الأشخاص: 6799 - المداخل: 6800 - التاريخ: 6789 - العدد: 6794 - الحالة: 7138";

    echo "Original text:\n";
    echo $test_text . "\n\n";

    echo "Processed text:\n";
    $processed = replaceNumericPlaceholders($test_text, $test_data);
    echo $processed . "\n\n";

    echo "Field values extracted:\n";
    $fields_to_test = [6977, 6788, 6786, 6973, 6983, 6984, 6796, 6797, 6798, 6799, 6800, 6789, 6794, 7138];
    foreach ($fields_to_test as $field) {
        $value = getFieldValue($test_data, $field, 'غير محدد');
        echo "Field $field: " . $value . "\n";
    }

    return $processed;
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'test_placeholders') {
        // Test the numeric placeholder system
        header('Content-Type: text/plain; charset=utf-8');
        testNumericPlaceholders();
        exit;
    }

    $quote_id = intval($_GET['quote_id'] ?? 0);

    if ($quote_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid quote ID']);
        exit;
    }

    if ($_GET['action'] === 'check_files') {
        $file_info = getFileInfo($quote_id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $file_info
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Debug: Log received data
    error_log("Received POST data: " . print_r($_POST, true));

    $quote_id = intval($_POST['quote_id'] ?? 0);

    if ($quote_id <= 0) {
        error_log("Invalid quote ID: " . ($_POST['quote_id'] ?? 'not set'));
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid quote ID: ' . ($_POST['quote_id'] ?? 'not set')]);
        exit;
    }

    if ($_POST['action'] === 'generate') {
        error_log("Generating Word document for quote ID: " . $quote_id);
        $result = generateWordDocument($quote_id);
    } elseif ($_POST['action'] === 'Refresh_pdf_word') {
        error_log("Refreshing Word and PDF for quote ID: " . $quote_id);
        $result = Refresh_pdf_word($quote_id);
        error_log("Refresh result: " . print_r($result, true));
    } elseif ($_POST['action'] === 'generate_pdf') {
        error_log("Generating PDF for quote ID: " . $quote_id);
        $result = generatePDFFromTemplate($quote_id);
        error_log("PDF generation result: " . print_r($result, true));
    } elseif ($_POST['action'] === 'convert_pdf') {
        error_log("Converting existing Word to PDF for quote ID: " . $quote_id);
        $result = convertExistingWordToPDF($quote_id);
    } else {
        error_log("Invalid action: " . $_POST['action']);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $_POST['action']]);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Note: File serving is handled by dx.php, not here.
// This file only handles document generation via POST requests.
// Default response
http_response_code(400);
echo "Invalid request";
?>