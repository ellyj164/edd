<?php
/**
 * Site Audit Tool - PHP-only E-commerce Platform
 * Crawls site, logs issues, takes screenshots, generates report
 * Usage: php tools/site_audit.php [--output=path] [--max-pages=N] [--screenshots]
 */

require_once __DIR__ . '/../includes/init.php';

class SiteAuditor {
    private $baseUrl;
    private $maxPages;
    private $enableScreenshots;
    private $outputDir;
    private $reportFile;
    private $issues = [];
    private $crawledPages = [];
    private $brokenLinks = [];
    private $placeholders = [];
    private $localhostRefs = [];
    private $performanceData = [];
    
    public function __construct($config = []) {
        $this->baseUrl = $config['base_url'] ?? env('APP_URL', 'http://localhost');
        $this->maxPages = $config['max_pages'] ?? 50;
        $this->enableScreenshots = $config['screenshots'] ?? false;
        $this->outputDir = $config['output_dir'] ?? __DIR__ . '/../storage/audit_reports';
        
        // Create output directory
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $this->reportFile = $this->outputDir . "/site_audit_{$timestamp}.html";
        
        echo "🔍 Site Audit Tool - EPD E-commerce Platform\n";
        echo "📍 Base URL: {$this->baseUrl}\n";
        echo "📄 Max Pages: {$this->maxPages}\n";
        echo "📸 Screenshots: " . ($this->enableScreenshots ? 'Enabled' : 'Disabled') . "\n";
        echo "📂 Output: {$this->reportFile}\n\n";
    }
    
    public function runAudit() {
        echo "🚀 Starting comprehensive site audit...\n\n";
        
        // Define critical pages to check
        $criticalPages = [
            '/' => 'Homepage',
            '/login.php' => 'Login Page',
            '/register.php' => 'Registration Page',
            '/products.php' => 'Products Listing',
            '/cart.php' => 'Shopping Cart',
            '/checkout.php' => 'Checkout Process',
            '/admin/index.php' => 'Admin Dashboard',
            '/seller-center.php' => 'Seller Center',
            '/account.php' => 'Customer Account',
            '/verify-email.php?email=test@example.com' => 'Email Verification',
            '/help.php' => 'Help Center',
            '/contact.php' => 'Contact Page',
            '/healthz.php' => 'Health Check',
            '/readyz.php' => 'Readiness Check'
        ];
        
        // Audit each critical page
        foreach ($criticalPages as $path => $name) {
            $this->auditPage($path, $name);
        }
        
        // Check for forbidden content patterns
        $this->checkForbiddenPatterns();
        
        // Check database connectivity
        $this->checkDatabaseHealth();
        
        // Check file permissions
        $this->checkFilePermissions();
        
        // Generate comprehensive report
        $this->generateReport();
        
        echo "\n✅ Audit complete! Report generated: {$this->reportFile}\n";
        
        // Return exit code based on critical issues
        $criticalIssues = array_filter($this->issues, function($issue) {
            return $issue['severity'] === 'critical';
        });
        
        return empty($criticalIssues) ? 0 : 1;
    }
    
    private function auditPage($path, $name) {
        $url = rtrim($this->baseUrl, '/') . $path;
        echo "📄 Auditing: {$name} ({$url})\n";
        
        $startTime = microtime(true);
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'EPD-SiteAuditor/1.0'
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($content === false) {
            $this->addIssue('critical', "Page unreachable: {$name}", $url, 'Could not fetch page content');
            return;
        }
        
        $this->crawledPages[] = [
            'url' => $url,
            'name' => $name,
            'size' => strlen($content),
            'response_time' => $responseTime
        ];
        
        // Performance check
        if ($responseTime > 2000) {
            $this->addIssue('warning', "Slow page load: {$name}", $url, "Response time: {$responseTime}ms");
        }
        
        // Content checks
        $this->checkPageContent($content, $url, $name);
        
        // Link validation
        $this->checkLinks($content, $url, $name);
        
        echo "   ✓ Response time: {$responseTime}ms, Size: " . number_format(strlen($content)) . " bytes\n";
    }
    
    private function checkPageContent($content, $url, $name) {
        // Check for placeholder content
        $placeholderPatterns = [
            '/placeholder/i',
            '/lorem ipsum/i',
            '/todo/i',
            '/coming soon/i',
            '/under construction/i',
            '/not yet implemented/i',
            '/\[placeholder\]/i'
        ];
        
        foreach ($placeholderPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->addIssue('warning', "Placeholder content found: {$name}", $url, "Pattern: {$pattern}");
                $this->placeholders[] = ['page' => $name, 'url' => $url, 'pattern' => $pattern];
            }
        }
        
        // Check for localhost references
        if (preg_match('/localhost/i', $content)) {
            $this->addIssue('high', "Localhost reference found: {$name}", $url, 'Page contains localhost URLs');
            $this->localhostRefs[] = ['page' => $name, 'url' => $url];
        }
        
        // Check for PHP errors
        if (preg_match('/Fatal error|Parse error|Warning:|Notice:/i', $content)) {
            $this->addIssue('critical', "PHP error found: {$name}", $url, 'Page contains PHP errors');
        }
        
        // Check for security indicators
        if (preg_match('/password|secret|key/i', $content) && preg_match('/plain|unencrypted/i', $content)) {
            $this->addIssue('critical', "Potential security issue: {$name}", $url, 'Sensitive data may be exposed');
        }
        
        // Check for essential elements
        if (strpos($content, '<title>') === false) {
            $this->addIssue('medium', "Missing title tag: {$name}", $url, 'Page lacks proper title');
        }
        
        if (strpos($content, 'csrf_token') === false && preg_match('/<form/i', $content)) {
            $this->addIssue('high', "Missing CSRF protection: {$name}", $url, 'Forms without CSRF tokens');
        }
    }
    
    private function checkLinks($content, $url, $name) {
        // Extract all links
        preg_match_all('/<a[^>]+href=["\'](.*?)["\'][^>]*>/i', $content, $matches);
        
        foreach ($matches[1] as $link) {
            // Skip external links and anchors
            if (strpos($link, 'http') === 0 && strpos($link, $this->baseUrl) !== 0) {
                continue;
            }
            
            if (strpos($link, '#') === 0 || strpos($link, 'javascript:') === 0) {
                continue;
            }
            
            // Check if link is broken
            $fullLink = $this->resolveUrl($link, $url);
            if (!$this->isValidLink($fullLink)) {
                $this->addIssue('medium', "Broken link in {$name}", $url, "Link: {$link}");
                $this->brokenLinks[] = ['page' => $name, 'url' => $url, 'link' => $link];
            }
        }
    }
    
    private function checkForbiddenPatterns() {
        echo "🔍 Checking for forbidden patterns in codebase...\n";
        
        $forbiddenPatterns = [
            'localhost' => 'Localhost references found',
            'TODO' => 'Unfinished TODO items',
            'FIXME' => 'Code marked for fixing',
            'console.log' => 'Debug console statements',
            'var_dump' => 'PHP debug statements',
            'print_r' => 'PHP debug output'
        ];
        
        $excludeDirs = ['vendor', 'phpmyadmin', 'node_modules', '.git'];
        
        foreach ($forbiddenPatterns as $pattern => $description) {
            $this->searchPattern($pattern, $description, $excludeDirs);
        }
    }
    
    private function searchPattern($pattern, $description, $excludeDirs) {
        $found = false;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(php|js|html|css)$/', $file->getFilename())) {
                $path = $file->getPathname();
                
                // Skip excluded directories
                $skip = false;
                foreach ($excludeDirs as $excludeDir) {
                    if (strpos($path, $excludeDir) !== false) {
                        $skip = true;
                        break;
                    }
                }
                
                if ($skip) continue;
                
                $content = file_get_contents($path);
                if (stripos($content, $pattern) !== false) {
                    $relativePath = str_replace(__DIR__ . '/../', '', $path);
                    $this->addIssue('info', $description, $relativePath, "Pattern '{$pattern}' found");
                    $found = true;
                }
            }
        }
        
        if (!$found) {
            echo "   ✓ No '{$pattern}' patterns found\n";
        }
    }
    
    private function checkDatabaseHealth() {
        echo "🗄️  Checking database connectivity...\n";
        
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
            $result = $stmt->fetch();
            
            echo "   ✓ Database connected, {$result['table_count']} tables found\n";
            
            // Check critical tables
            $criticalTables = ['users', 'products', 'orders', 'email_tokens', 'login_attempts'];
            foreach ($criticalTables as $table) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table}");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                echo "   ✓ Table '{$table}': {$count} records\n";
            }
            
        } catch (Exception $e) {
            $this->addIssue('critical', 'Database connection failed', 'Database', $e->getMessage());
        }
    }
    
    private function checkFilePermissions() {
        echo "🔒 Checking file permissions...\n";
        
        $checkPaths = [
            'storage' => ['writable' => true],
            'uploads' => ['writable' => true], 
            'logs' => ['writable' => true],
            '.env' => ['writable' => false]
        ];
        
        foreach ($checkPaths as $path => $requirements) {
            $fullPath = __DIR__ . '/../' . $path;
            
            if (!file_exists($fullPath)) {
                $this->addIssue('warning', "Missing directory/file: {$path}", $path, 'Path does not exist');
                continue;
            }
            
            $writable = is_writable($fullPath);
            $readable = is_readable($fullPath);
            
            if ($requirements['writable'] && !$writable) {
                $this->addIssue('high', "Permission issue: {$path}", $path, 'Should be writable');
            } elseif (!$requirements['writable'] && $writable) {
                $this->addIssue('medium', "Security concern: {$path}", $path, 'Should not be writable');
            }
            
            echo "   " . ($readable ? '✓' : '✗') . " Readable: {$path}\n";
            echo "   " . ($writable ? '✓' : '✗') . " Writable: {$path}\n";
        }
    }
    
    private function addIssue($severity, $title, $location, $description) {
        $this->issues[] = [
            'severity' => $severity,
            'title' => $title,
            'location' => $location,
            'description' => $description,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $icon = match($severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '⚡',
            'warning' => '💛',
            'info' => 'ℹ️',
            default => '🔍'
        };
        
        echo "   {$icon} [{$severity}] {$title}: {$description}\n";
    }
    
    private function resolveUrl($link, $baseUrl) {
        if (strpos($link, 'http') === 0) {
            return $link;
        }
        
        if (strpos($link, '/') === 0) {
            return $this->baseUrl . $link;
        }
        
        return dirname($baseUrl) . '/' . $link;
    }
    
    private function isValidLink($url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 5
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        return $headers && strpos($headers[0], '200') !== false;
    }
    
    private function generateReport() {
        echo "📊 Generating comprehensive audit report...\n";
        
        $html = $this->generateReportHTML();
        file_put_contents($this->reportFile, $html);
        
        // Also generate JSON summary
        $jsonFile = str_replace('.html', '.json', $this->reportFile);
        $summary = [
            'audit_date' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'pages_crawled' => count($this->crawledPages),
            'total_issues' => count($this->issues),
            'issues_by_severity' => $this->getIssuesBySeverity(),
            'broken_links' => count($this->brokenLinks),
            'placeholder_pages' => count($this->placeholders),
            'localhost_references' => count($this->localhostRefs),
            'performance' => $this->getPerformanceSummary()
        ];
        
        file_put_contents($jsonFile, json_encode($summary, JSON_PRETTY_PRINT));
        
        echo "   ✓ HTML Report: {$this->reportFile}\n";
        echo "   ✓ JSON Summary: {$jsonFile}\n";
    }
    
    private function generateReportHTML() {
        $issuesBySeverity = $this->getIssuesBySeverity();
        $timestamp = date('Y-m-d H:i:s');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Site Audit Report - <?php echo date('Y-m-d H:i:s'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1, h2 { color: #333; }
                .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
                .metric { background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center; }
                .metric-value { font-size: 2em; font-weight: bold; color: #0052cc; }
                .metric-label { color: #666; margin-top: 5px; }
                .issue { margin-bottom: 15px; padding: 15px; border-radius: 6px; border-left: 4px solid #ddd; }
                .critical { border-left-color: #dc3545; background: #fff5f5; }
                .high { border-left-color: #fd7e14; background: #fff8f1; }
                .medium { border-left-color: #ffc107; background: #fffcf0; }
                .warning { border-left-color: #20c997; background: #f0fff9; }
                .info { border-left-color: #6f42c1; background: #f8f5ff; }
                .issue-title { font-weight: bold; margin-bottom: 5px; }
                .issue-location { color: #666; font-size: 0.9em; }
                .issue-description { margin-top: 8px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; font-weight: 600; }
                .status-good { color: #28a745; font-weight: bold; }
                .status-warning { color: #ffc107; font-weight: bold; }
                .status-error { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔍 Site Audit Report</h1>
                <p><strong>Base URL:</strong> <?php echo htmlspecialchars($this->baseUrl); ?></p>
                <p><strong>Audit Date:</strong> <?php echo $timestamp; ?></p>
                
                <div class="summary">
                    <div class="metric">
                        <div class="metric-value"><?php echo count($this->crawledPages); ?></div>
                        <div class="metric-label">Pages Crawled</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo count($this->issues); ?></div>
                        <div class="metric-label">Total Issues</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $issuesBySeverity['critical'] ?? 0; ?></div>
                        <div class="metric-label">Critical Issues</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo count($this->brokenLinks); ?></div>
                        <div class="metric-label">Broken Links</div>
                    </div>
                </div>
                
                <h2>📋 Issues Found</h2>
                <?php if (empty($this->issues)): ?>
                    <p class="status-good">✅ No issues found! Site audit passed.</p>
                <?php else: ?>
                    <?php foreach ($this->issues as $issue): ?>
                        <div class="issue <?php echo $issue['severity']; ?>">
                            <div class="issue-title"><?php echo htmlspecialchars($issue['title']); ?></div>
                            <div class="issue-location">📍 <?php echo htmlspecialchars($issue['location']); ?></div>
                            <div class="issue-description"><?php echo htmlspecialchars($issue['description']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <h2>📄 Pages Crawled</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>URL</th>
                            <th>Response Time</th>
                            <th>Size</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->crawledPages as $page): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($page['name']); ?></td>
                                <td><a href="<?php echo htmlspecialchars($page['url']); ?>" target="_blank"><?php echo htmlspecialchars($page['url']); ?></a></td>
                                <td><?php echo $page['response_time']; ?>ms</td>
                                <td><?php echo number_format($page['size']); ?> bytes</td>
                                <td class="<?php echo $page['response_time'] > 2000 ? 'status-warning' : 'status-good'; ?>">
                                    <?php echo $page['response_time'] > 2000 ? '⚠️ Slow' : '✅ OK'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($this->brokenLinks)): ?>
                <h2>🔗 Broken Links</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Broken Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->brokenLinks as $link): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($link['page']); ?></td>
                                <td><?php echo htmlspecialchars($link['link']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
                <h2>📊 Summary</h2>
                <p>This audit report was generated by the EPD Site Auditor tool to ensure production readiness and identify potential issues.</p>
                <ul>
                    <li><strong>Critical Issues:</strong> <?php echo $issuesBySeverity['critical'] ?? 0; ?> (must be fixed before production)</li>
                    <li><strong>High Priority:</strong> <?php echo $issuesBySeverity['high'] ?? 0; ?> (should be fixed soon)</li>
                    <li><strong>Medium Priority:</strong> <?php echo $issuesBySeverity['medium'] ?? 0; ?> (recommended fixes)</li>
                    <li><strong>Warnings:</strong> <?php echo $issuesBySeverity['warning'] ?? 0; ?> (monitor these)</li>
                    <li><strong>Informational:</strong> <?php echo $issuesBySeverity['info'] ?? 0; ?> (for awareness)</li>
                </ul>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function getIssuesBySeverity() {
        $counts = [];
        foreach ($this->issues as $issue) {
            $counts[$issue['severity']] = ($counts[$issue['severity']] ?? 0) + 1;
        }
        return $counts;
    }
    
    private function getPerformanceSummary() {
        if (empty($this->crawledPages)) return [];
        
        $responseTimes = array_column($this->crawledPages, 'response_time');
        return [
            'avg_response_time' => round(array_sum($responseTimes) / count($responseTimes), 2),
            'max_response_time' => max($responseTimes),
            'min_response_time' => min($responseTimes)
        ];
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['output:', 'max-pages:', 'screenshots']);
    
    $config = [
        'output_dir' => $options['output'] ?? null,
        'max_pages' => isset($options['max-pages']) ? (int)$options['max-pages'] : 50,
        'screenshots' => isset($options['screenshots'])
    ];
    
    $auditor = new SiteAuditor($config);
    $exitCode = $auditor->runAudit();
    exit($exitCode);
}