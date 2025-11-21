<?php

// Extract the download form from the virus scan warning page
$fileId = "188nthRbTF51QhJ2x7eja8NCGcwcYqOyP";
$url = "https://drive.usercontent.google.com/download?id={$fileId}&export=download";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
]);

$html = curl_exec($ch);
curl_close($ch);

echo "=== Extracting Form Data ===\n\n";

// Extract form action
if (preg_match('/<form[^>]*action="([^"]+)"/', $html, $matches)) {
    echo "Form action: {$matches[1]}\n\n";
}

// Extract all form inputs
echo "Form inputs:\n";
if (preg_match_all('/<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        echo "  {$match[1]} = {$match[2]}\n";
    }
}

// Try to find UUID
if (preg_match('/name="uuid"[^>]*value="([^"]+)"/', $html, $matches)) {
    $uuid = $matches[1];
    echo "\n=== Found UUID: $uuid ===\n";
    
    // Build the actual download URL
    $downloadUrl = "https://drive.usercontent.google.com/download?id={$fileId}&export=download&confirm=t&uuid={$uuid}";
    echo "Constructed download URL: $downloadUrl\n\n";
    
    // Try to download with this URL
    echo "=== Testing constructed URL ===\n";
    $ch = curl_init($downloadUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RANGE => "0-3",
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    
    $bytes = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Content-Type: $contentType\n";
    echo "Body length: " . strlen($bytes) . "\n";
    
    if (strlen($bytes) >= 4) {
        echo "First 4 bytes (hex): ";
        for ($i = 0; $i < 4; $i++) {
            echo sprintf("%02X ", ord($bytes[$i]));
        }
        echo "\n";
        
        $isZip = ord($bytes[0]) === 0x50 && 
                 ord($bytes[1]) === 0x4B && 
                 ord($bytes[2]) === 0x03 && 
                 ord($bytes[3]) === 0x04;
        echo "Is ZIP signature: " . ($isZip ? "YES ✓✓✓" : "NO ✗") . "\n";
    }
}

// Show the full HTML around the form
echo "\n=== Full HTML (for analysis) ===\n";
if (preg_match('/<form.*?<\/form>/s', $html, $matches)) {
    echo $matches[0] . "\n";
}