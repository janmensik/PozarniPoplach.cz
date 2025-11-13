<?php

/**
 * Decodes a Base64 string that was originally encoded from a specific character set.
 *
 * @param string $base64String The Base64 encoded string.
 * @param string $originalCharset The character set of the original, pre-encoded data (e.g., 'Windows-1250').
 * @return string The decoded string, converted to UTF-8 for modern use.
 */
function decode_base64_with_charset(string $base64String, string $originalCharset): string
{
    // Step 1: Decode the Base64 string into its raw byte representation.
    // The function automatically handles whitespace like newlines in the input string.
    $rawBytes = base64_decode($base64String);

    // Step 2: Convert the character encoding of the raw bytes from the original
    // charset to UTF-8. This makes it display correctly in modern browsers/terminals.
    // This requires the 'mbstring' PHP extension, which is usually enabled by default.
    $utf8Text = mb_convert_encoding($rawBytes, 'UTF-8', $originalCharset);

    return $utf8Text;
}

// The Base64 string from your 'Untitled-2.js' file.
$base64_input = 'PGh0bWw+CjxoZWFkPgogICAgPHRpdGxlPjwvdGl0bGU+CjwvaGVhZD4KICA8Ym9keT4KICA8ZGl2IHN0eWxlPSJmb250LWZhbWlseTogVGltZXMgTmV3IFJvbWFuOyB3aWR0aDogNjUwcHg7Ij5MaWLoaWNlIG5hZCBWbHRhdm91PGJyLz48YnIvPjxiPjxiaWc+UE+OwVIgLSBOzVpLySBCVURPVlk8L2JpZz48L2I+PGJyLz48aHIvPjxpPkFkcmVzYSB1ZOFsb3N0aTo8L2k+PGJyLz5LUkFKOiAmbmJzcDsgJm5ic3A7IDxiPjxiaWc+PGJpZz5TVNhFRE/IRVNL3TwvYmlnPjwvYmlnPjwvYj48YnIvPk9CRUM6ICZuYnNwOyAmbmJzcDs8Yj48YmlnPjxiaWc+TElCyElDRSBOQUQgVkxUQVZPVSAmbmJzcDsgPC9iaWc+PC9iaWc+PC9iPihva3IuOiBQcmFoYS164XBhZCk8YnIvPkPBU1Q6ICZuYnNwOyAmbmJzcDsgPGI+PGJpZz5MaWLoaWNlIG5hZCBWbHRhdm91PC9iaWc+PC9iPjxici8+PGJyLz5VTElDRTogJm5ic3A7ICZuYnNwOzxiPjxiaWc+PGJpZz5MRVRFQ0vBPC9iaWc+PC9iaWc+PC9iPuguIHAgLiZuYnNwOyAmbmJzcDsgJm5ic3A7PGI+PGJpZz41MjI8L2JpZz48L2I+PGJyLz48YnIvPkdQUzogJm5ic3A7ICZuYnNwOyAmbmJzcDsgJm5ic3A7IDxiPjUwLjE5NTM0MyBOLCAxNC4zNzA4NzggRTwvYj48YnIvPgogICAgICAgIDxoci8+CiAgICAgICAgT0JKRUtUOiAmbmJzcDsgPGI+PGJpZz48YmlnPjwvYmlnPjwvYmlnPjwvYj48YnIvPgogICAgICAgICAgICAgICAgPGhyLz4KCiAgICAgICAgVVBSRVNORU7NOjxici8+PGI+PGJpZz48L2JpZz48L2I+PGJyLz48YnIvPkNPIFNFIFNUQUxPOjxici8+PGI+PGJpZz5ob/jtIHYgYmFyYWt1IC0gamVkbuEgc2UgbyBCRCAyIHBvc2Nob2TtPC9iaWc+PC9iPjxici8+PGJyLz4KICAgICAgICA8aHIvPjxhIGhyZWY9Imh0dHBzOi8vd3d3Lmdvb2dsZS5jb20vbWFwcy9zZWFyY2gvP2FwaT0xJnF1ZXJ5PTUwLjE5NTM0MywxNC4zNzA4NzgiPkdvb2dsZSBtYXBhPC9hPjxici8+PGEgaHJlZj0iaHR0cHM6Ly9tYXB5LmN6L3pha2xhZG5pP3g9MTQuMzcwODc4Jnk9NTAuMTk1MzQzLCZ6PTE3JnNvdXJjZT1jb29yJmlkPTE0LjM3MDg3OCUyQzUwLjE5NTM0MyI+TWFweS5jejwvYT48aHIvPk9aTsFNSUw6ICZuYnNwOyAmbmJzcDsgPGI+ICZuYnNwOyAmbmJzcDsgJm5ic3A7ICZuYnNwOyAmbmJzcDsgJm5ic3A7IDwvYj5UZWxlZm9uOiAmbmJzcDsgJm5ic3A7IDxiPjA2MDg4NDAzMTU8L2I+PGJyLz48aHIvPlRFQ0hOSUtBIExpYuhpY2UgbmFkIFZsdGF2b3U6PGJyLz48YmlnPjxiPkNBUyAyNS8yNTAwLzAgTTJSIExJQVogMTAxPC9iPiAtIEhQWiA0MTI8YnIvPjwvYmlnPjxici8+PGJyLz4KCiAgICAgICAgICAgICAgICA8aT5URUNITklLQSBkYWya7WNoIGplZG5vdGVrIFBPOjwvYT48YnIvPjxiaWc+OiAgLSA8YnIvPkhTIFJvenRva3k6IENBUyAyMC80MDAwLzI0MCBTMlQgU0NBTklBIC0gUFBaIDEyMTxici8+Q0hTIEtsYWRubzogVkVBIEwyIEZPUkQgUkFOR0VSICAtIFBLTCAxMTU8YnIvPkhTIFJvenRva3k6IENBUyAzMC84MDAwLzgwMCBTMkxQIFNDQU5JQSAgLSBQUFogMTIyPGJyLz48L2JpZz4KICAgICAgIDxoci8+CgogICAgICAgIDxzbWFsbD48aT5VZOFsb3N0IGMuIDI4OTEyMTIwMjEgLSBvZGJhdmlsIFByYWNvdmmadOwgNSBPRCAtIDE5LjA4LjIwMjUgMTc6MDI6NTk8L2k+PC9zbWFsbD4KICA8L2Rpdj4KICA8L2JvZHk+CjwvaHRtbD4A';

// The original encoding was Windows-1250
$original_charset = 'Windows-1250';

// Perform the decoding
$decoded_html = decode_base64_with_charset($base64_input, $original_charset);

header('Content-Type: text/html; charset=utf-8');
echo $decoded_html;

?>