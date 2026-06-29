<?php
/**
 * PHPue WordPress Plugin Bundler (Cross-Platform PHP Native)
 */

$pluginSlug = "phpue-elementor-widget";
$zipName = "{$pluginSlug}.zip";

echo "⚡ Starting PHPue WordPress Plugin Bundler (Pure PHP)...\n";

// Remove old archive if it exists
if (file_exists($zipName)) {
    echo "🧹 Removing old archive...\n";
    unlink($zipName);
}

$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("❌ Error: Cannot create zip file archive.\n");
}

echo "📦 Compiling raw source files into production build...\n";

// Recursive directory iterator
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$excludePatterns = [
    '/\.git/',
    '/bundler\.php/',
    '/bundle\.sh/',
    '/\.DS_Store/',
    '/\.vscode/'
];

foreach ($files as $name => $file) {
    // Skip directories (they are added automatically with files)
    if ($file->isDir()) {
        continue;
    }

    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen(realpath('.')) + 1);

    // Normalize Windows backslashes for clean zipping
    $relativePath = str_replace('\\', '/', $relativePath);

    // Check against exclusion patterns
    foreach ($excludePatterns as $pattern) {
        if (preg_match($pattern, $relativePath)) {
            continue 2; // Skip this file entirely
        }
    }

    // Add file to zip archive
    $zip->addFile($filePath, $relativePath);
}

$zip->close();

echo "🎉 Success! Created: {$zipName}\n";