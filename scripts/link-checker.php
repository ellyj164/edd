#!/usr/bin/env php
<?php
/**
 * FezaMarket Link Checker
 * Crawls the site and identifies broken internal links
 */

// Configuration
$baseUrl = $argv[1] ?? 'http://localhost:8000';
$maxPages = (int)($argv[2] ?? 100);
$outputFile = __DIR__ . '/../docs/link-report.json';

echo "🔍 FezaMarket Link Checker\n";
echo "==========================\n\n";
echo "Base URL: {$baseUrl}\n";
echo "Max Pages: {$maxPages}\n\n";

// Initialize
$visited = [];
$queue = ['/'];
$brokenLinks = [];
$allLinks = [];
$pageCount = 0;

// Helper function to get absolute URL
function getAbsoluteUrl($base, $relative) {
    if (preg_match('/^https?:\/\//', $relative)) {
        return $relative;
    }
    
    if ($relative[0] === '/') {
        return rtrim($base, '/') . $relative;
    }
    
    return $base . '/' . ltrim($relative, '/');
}

// Helper function to extract links from HTML
function extractLinks($html, $baseUrl) {
    $links = [];
    preg_match_all('/<a[^>]+href=["\'](.*?)["\']/', $html, $matches);
    
    foreach ($matches[1] as $link) {
        // Skip anchors, external links, mailto, tel, etc.
        if (preg_match('/^(#|mailto:|tel:|javascript:)/', $link)) {
            continue;
        }
        
        // Skip external URLs
        if (preg_match('/^https?:\/\//', $link) && strpos($link, parse_url($baseUrl, PHP_URL_HOST)) === false) {
            continue;
        }
        
        // Clean up query parameters and fragments
        $cleanLink = preg_replace('/[?#].*$/', '', $link);
        
        if (!empty($cleanLink)) {
            $links[] = $cleanLink;
        }
    }
    
    return array_unique($links);
}

echo "Starting crawl...\n\n";

// Crawl pages
while (!empty($queue) && $pageCount < $maxPages) {
    $path = array_shift($queue);
    
    if (in_array($path, $visited)) {
        continue;
    }
    
    $visited[] = $path;
    $pageCount++;
    
    $url = getAbsoluteUrl($baseUrl, $path);
    echo "[{$pageCount}/{$maxPages}] Checking: {$path}\n";
    
    // Fetch page
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $brokenLinks[] = [
            'url' => $path,
            'status' => $httpCode,
            'type' => 'page_error'
        ];
        echo "  ❌ Error {$httpCode}\n";
        continue;
    }
    
    echo "  ✓ OK\n";
    
    // Extract and check links
    $links = extractLinks($html, $baseUrl);
    
    foreach ($links as $link) {
        $allLinks[] = [
            'from' => $path,
            'to' => $link
        ];
        
        // Add to queue if not visited
        if (!in_array($link, $visited) && !in_array($link, $queue)) {
            $queue[] = $link;
        }
    }
}

echo "\n\nCrawl complete!\n";
echo "================\n\n";

// Check for broken links
echo "Checking all discovered links...\n\n";

$checkedUrls = [];
foreach ($allLinks as $linkInfo) {
    $link = $linkInfo['to'];
    
    if (in_array($link, $checkedUrls)) {
        continue;
    }
    
    $checkedUrls[] = $link;
    $url = getAbsoluteUrl($baseUrl, $link);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $brokenLinks[] = [
            'url' => $link,
            'status' => $httpCode,
            'type' => 'broken_link',
            'found_on' => $linkInfo['from']
        ];
        echo "❌ {$link} (status: {$httpCode})\n";
    }
}

// Generate report
$report = [
    'timestamp' => date('c'),
    'base_url' => $baseUrl,
    'pages_crawled' => $pageCount,
    'total_links' => count($allLinks),
    'unique_links' => count($checkedUrls),
    'broken_links' => count($brokenLinks),
    'broken_links_details' => $brokenLinks,
    'all_pages' => $visited
];

// Create docs directory if it doesn't exist
$docsDir = dirname($outputFile);
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0755, true);
}

// Save report
file_put_contents($outputFile, json_encode($report, JSON_PRETTY_PRINT));

echo "\n\n📊 Summary\n";
echo "==========\n";
echo "Pages Crawled: {$pageCount}\n";
echo "Total Links: " . count($allLinks) . "\n";
echo "Unique Links: " . count($checkedUrls) . "\n";
echo "Broken Links: " . count($brokenLinks) . "\n\n";

if (count($brokenLinks) > 0) {
    echo "❌ Found " . count($brokenLinks) . " broken link(s):\n\n";
    foreach ($brokenLinks as $broken) {
        echo "  • {$broken['url']} (status: {$broken['status']})";
        if (isset($broken['found_on'])) {
            echo " - found on: {$broken['found_on']}";
        }
        echo "\n";
    }
    echo "\n";
} else {
    echo "✅ No broken links found!\n\n";
}

echo "Report saved to: {$outputFile}\n\n";

exit(count($brokenLinks) > 0 ? 1 : 0);
