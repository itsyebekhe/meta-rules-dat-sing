<?php

// --- Configuration ---

// The common part of the URL that should be stripped to get the relative file path.
// This is used to determine the folder structure.
$commonUrlPrefix = 'https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/';

// The JSON data containing the file information.
$jsonString = '[
  {
    "download_detour": "direct",
    "format": "binary",
    "tag": "geosite-ads",
    "type": "remote",
    "url": "https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geosite/category-ads-all.srs"
  },
  {
    "download_detour": "direct",
    "format": "binary",
    "tag": "geosite-private",
    "type": "remote",
    "url": "https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geosite/private.srs"
  },
  {
    "download_detour": "direct",
    "format": "binary",
    "tag": "geosite-ir",
    "type": "remote",
    "url": "https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geosite/category-ir.srs"
  },
  {
    "download_detour": "direct",
    "format": "binary",
    "tag": "geoip-private",
    "type": "remote",
    "url": "https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geoip/private.srs"
  },
  {
    "download_detour": "direct",
    "format": "binary",
    "tag": "geoip-ir",
    "type": "remote",
    "url": "https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geoip/ir.srs"
  }
]';


// --- Script Logic ---

// Decode the JSON data
$items = json_decode($jsonString, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Invalid JSON data provided. " . json_last_error_msg() . "\n");
}

echo "Starting download process. Files will be saved in subdirectories in the current folder.\n\n";
echo "WARNING: This will create folders (e.g., 'geo') in the current directory.\n\n";

// Loop through each item to download
foreach ($items as $item) {
    if (empty($item['url'])) {
        echo "Skipping item with no URL: " . ($item['tag'] ?? 'N/A') . "\n\n";
        continue;
    }

    $url = $item['url'];
    echo "Processing URL: $url\n";

    // 1. Determine the relative path for the file
    if (strpos($url, $commonUrlPrefix) !== 0) {
        echo " -> ERROR: URL does not match the expected prefix. Skipping.\n\n";
        continue;
    }
    // The file path is now relative to the script's location
    $filePath = substr($url, strlen($commonUrlPrefix));
    
    // 2. Create the destination directory if it doesn't exist
    $directoryPath = dirname($filePath);
    if (!is_dir($directoryPath)) {
        echo " -> Creating directory: $directoryPath\n";
        // Create the directory structure recursively
        if (!mkdir($directoryPath, 0775, true)) {
            echo " -> ERROR: Failed to create directory '$directoryPath'. Skipping.\n\n";
            continue;
        }
    }

    // 3. Download the file using cURL
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Check for download errors
    if ($content === false || $httpCode !== 200) {
        echo " -> ERROR: Failed to download file. HTTP Status: $httpCode\n";
        if ($error) {
            echo "    -> cURL Error: $error\n";
        }
        echo "\n";
        continue;
    }

    // 4. Save the content to the file
    $bytesWritten = file_put_contents($filePath, $content);

    if ($bytesWritten === false) {
        echo " -> ERROR: Failed to save file to $filePath.\n\n";
    } else {
        echo " -> Success! Saved to: $filePath ($bytesWritten bytes)\n\n";
    }
}

echo "All tasks completed.\n";
