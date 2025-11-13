<?php

/**
 * Parses the HTML content of a dispatch report and extracts structured data.
 *
 * @param string $htmlContent The HTML content from the file.
 * @return array An associative array containing the extracted data.
 */
function parseDispatchHtml(string $htmlContent): array {
    // The HTML is not well-formed, so we suppress warnings from the parser.
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // Prepending the XML encoding declaration helps DOMDocument handle UTF-8 characters correctly.
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $data = [];

    /**
     * Helper function to execute an XPath query and return the trimmed text content of the first result.
     * It cleans up whitespace and non-breaking spaces.
     * @param string $query The XPath query string.
     * @return string|null The cleaned text content or null if not found.
     */
    $queryValue = function (string $query) use ($xpath): ?string {
        $node = $xpath->query($query)->item(0);
        if ($node) {
            // \xC2\xA0 is the UTF-8 non-breaking space (&nbsp;)
            return trim($node->textContent, " \t\n\r\0\x0B\xC2\xA0");
        }
        return null;
    };

    // --- Extraction Logic ---

    // Location Header (first text node in the main div)
    $data['location_header'] = $queryValue('//div/text()[1]');

    // Event Type
    $data['event_type'] = $queryValue('//div/b/big');

    // Address Details
    $data['address']['region'] = $queryValue('//text()[contains(., "KRAJ:")]/following-sibling::b[1]');

    // Municipality and District are on the same line, but district is outside the <b> tag.
    $municipalityNode = $xpath->query('//text()[contains(., "OBEC:")]/following-sibling::b[1]')->item(0);
    if ($municipalityNode) {
        $data['address']['municipality'] = trim($municipalityNode->textContent);

        // The district info is in the next text node sibling
        $districtNode = $municipalityNode->nextSibling;
        if ($districtNode && $districtNode->nodeType === XML_TEXT_NODE) {
            // Extract text from within the parentheses, e.g., "(okr.: Praha-západ)"
            if (preg_match('/\(okr\.:\s*(.*?)\)/', $districtNode->textContent, $matches)) {
                $data['address']['district'] = trim($matches[1]);
            }
        }
    }

    $data['address']['part'] = $queryValue('//text()[contains(., "ČÁST:")]/following-sibling::b[1]');
    $data['address']['street'] = $queryValue('//text()[contains(., "ULICE:")]/following-sibling::b[1]');
    $data['address']['house_number'] = $queryValue('//text()[contains(., "č. p .")]/following-sibling::b[1]');
    $data['address']['gps'] = $queryValue('//text()[contains(., "GPS:")]/following-sibling::b[1]');

    // Object Description
    $data['object_description'] = $queryValue('//text()[contains(., "OBJEKT:")]/following-sibling::b[1]');

    // Clarification
    $data['clarification'] = $queryValue('//text()[contains(., "UPŘESNĚNÍ:")]/following-sibling::br[1]/following-sibling::b[1]');

    // What Happened
    $data['what_happened'] = $queryValue('//text()[contains(., "CO SE STALO:")]/following-sibling::br[1]/following-sibling::b[1]');

    // Map Links
    $data['links']['google_maps'] = $queryValue('//a[text()="Google mapa"]/@href');
    $data['links']['mapy_cz'] = $queryValue('//a[text()="Mapy.cz"]/@href');

    // Notification Phone
    $data['notification']['phone'] = $queryValue('//text()[contains(., "Telefon:")]/following-sibling::b[1]');

    // Local Vehicles (can be a list)
    $localVehiclesNode = $xpath->query('//text()[contains(., "TECHNIKA Libčice nad Vltavou:")]/following-sibling::big[1]')->item(0);
    if ($localVehiclesNode) {
        $vehiclesText = trim($localVehiclesNode->textContent);
        // Split by newline and filter out any empty lines that result
        $data['local_vehicles'] = array_values(array_filter(array_map('trim', explode("\n", $vehiclesText))));
    } else {
        $data['local_vehicles'] = [];
    }

    // Other Vehicles (a list of vehicles from other units)
    $otherVehiclesNode = $xpath->query('//i[contains(., "TECHNIKA dalších jednotek PO:")]/following-sibling::big[1]')->item(0);
    if ($otherVehiclesNode) {
        $vehiclesText = trim($otherVehiclesNode->textContent);
        $vehiclesList = array_map('trim', explode("\n", $vehiclesText));

        // Filter out empty lines and the ": -" placeholder
        $data['other_vehicles'] = array_values(array_filter($vehiclesList, function ($line) {
            return !empty($line) && trim($line) !== ':-';
        }));
    } else {
        $data['other_vehicles'] = [];
    }

    // Footer Info
    $footerInfo = $queryValue('//small/i');
    if ($footerInfo) {
        $data['footer_info'] = $footerInfo;
        // You could further parse the footer info if needed
        // Example: preg_match('/Událost č. (\d+) - odbavil (.+) - ([\d\. :]+)/', $footerInfo, $matches)
    }

    return $data;
}

// --- Example Usage ---

// Place this script in the same directory as your HTML file, or update the path.
$htmlFilePath = 'Untitled-3.html';
if (!file_exists($htmlFilePath)) {
    die("Error: File not found at '{$htmlFilePath}'");
}
$htmlContent = file_get_contents($htmlFilePath);

// Parse the HTML to get the structured data
$extractedData = parseDispatchHtml($htmlContent);

// Print the resulting array to see the output
echo "--- Extracted Data ---" . PHP_EOL;
print_r($extractedData);

/*
 * Now you have the data in a structured PHP array ($extractedData).
 * You can access any value like this:
 * $eventType = $extractedData['event_type'];
 * $street = $extractedData['address']['street'];
 * $firstOtherVehicle = $extractedData['other_vehicles'][0] ?? 'N/A';
 *
 * From here, you can easily prepare and execute an SQL INSERT statement
 * using PDO or mysqli, mapping the array keys to your table columns.
 *
 * Example with PDO (conceptual):
 * $stmt = $pdo->prepare("INSERT INTO dispatches (event_type, street, house_number, gps) VALUES (?, ?, ?, ?)");
 * $stmt->execute([
 *     $extractedData['event_type'],
 *     $extractedData['address']['street'],
 *     $extractedData['address']['house_number'],
 *     $extractedData['address']['gps']
 * ]);
 */
