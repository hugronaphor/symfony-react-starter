<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * ProjectSnapshotGenerator Service.
 *
 * Generates a snapshot of the project's source files as a plain text file.
 * Respects .gitignore patterns and provides AI-friendly formatting.
 */
class ProjectSnapshotGenerator
{
    /**
     * Configuration constants.
     */
    private const array CONFIG = [
        // File extensions to include only
        'INCLUDE_EXTENSIONS' => [
            'php', 'yaml', 'yml', 'json', 'md', 'txt', 'html', 'twig',
            'jsx', 'tsx', 'ts', 'js', 'css', 'scss', 'sql', 'neon', 'xml',
        ],
    ];

    /**
     * Files/paths to exclude explicitly (in addition to gitignore).
     */
    private const array EXPLICIT_EXCLUDES = [
        '.ddev/',
        '.idea/',
        'package-lock.json',
        'composer.lock',
    ];

    /**
     * Large files to note but not include content
     * Format: relative path patterns that should be noted but skipped for content.
     */
    private const array SKIP_CONTENT_PATTERNS = [
        'assets/vendor/',
        'node_modules/',
    ];

    private string $projectRoot;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array<int, string>
     */
    private array $gitignorePatterns = [];

    /**
     * @var array<int, string>
     */
    private array $filesWithoutContent = [];

    /**
     * @param array<string, mixed>|null $customConfig
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectRoot,
        ?array $customConfig = null,
    ) {
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->config = $customConfig ? array_merge(self::CONFIG, $customConfig) : self::CONFIG;
        $this->loadGitignorePatterns();
    }

    /**
     * Load and parse .gitignore file.
     */
    private function loadGitignorePatterns(): void
    {
        $gitignorePath = $this->projectRoot.'/.gitignore';

        if (!file_exists($gitignorePath)) {
            return;
        }

        $lines = file($gitignorePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (false === $lines) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (str_starts_with($line, '#')) {
                continue;
            }

            // Convert gitignore patterns to exclusion patterns
            $this->gitignorePatterns[] = $line;
        }
    }

    /**
     * Check if a file path should be ignored based on .gitignore and explicit excludes.
     */
    private function shouldIgnore(string $relativePath): bool
    {
        // Check explicit excludes first
        if (array_any(self::EXPLICIT_EXCLUDES, fn ($pattern) => $this->matchesPattern($relativePath, $pattern))) {
            return true;
        }

        // Check gitignore patterns
        return array_any($this->gitignorePatterns, fn ($pattern) => $this->matchesPattern($relativePath, $pattern));
    }

    /**
     * Check if a file should be included but without its content.
     */
    private function shouldSkipContent(string $relativePath): bool
    {
        return array_any(self::SKIP_CONTENT_PATTERNS, fn ($pattern) => $this->matchesPattern($relativePath, $pattern));
    }

    /**
     * Check if a path matches a gitignore pattern.
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Remove leading/trailing slashes
        $pattern = trim($pattern, '/');

        // Handle directory patterns (ending with /)
        if (str_ends_with($pattern, '/')) {
            $pattern = rtrim($pattern, '/');

            return str_starts_with($path, $pattern.'/') || str_starts_with($path, $pattern);
        }

        // Handle wildcard patterns
        if (str_contains($pattern, '*')) {
            // Convert gitignore pattern to fnmatch pattern
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = str_replace('.', '\.', $pattern);
            $pattern = str_replace('*', '.*', $pattern);

            return 1 === preg_match("^{$pattern}^", $path)
                || 1 === preg_match("^{$pattern}/.*^", $path);
        }

        // Exact match or directory match
        return $path === $pattern
            || str_starts_with($path, $pattern.'/')
            || str_starts_with($path, $pattern);
    }

    /**
     * Generate the project snapshot as plain text.
     */
    public function generate(): string
    {
        $finder = new Finder();

        /**
         * @var array<int, SplFileInfo> $files
         */
        $files = [];
        $this->filesWithoutContent = [];

        // Build the finder - scan all files in project
        $finder
            ->files()
            ->in($this->projectRoot)
            ->ignoreDotFiles(false)
            ->ignoreVCS(true)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('var')
            ->exclude('.git')
            ->exclude('public/builds')
            ->exclude('.ddev');

        // Apply extension filter (only include relevant file types)
        $extensions = implode('|', $this->config['INCLUDE_EXTENSIONS']);
        $finder->name("/\.({$extensions})$/i");

        // Collect files, respecting .gitignore and explicit excludes
        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();

            // Skip if matches gitignore patterns or explicit excludes
            if ($this->shouldIgnore($relativePath)) {
                continue;
            }

            // Check if we should skip content for this file
            if ($this->shouldSkipContent($relativePath)) {
                $this->filesWithoutContent[] = $relativePath;
            }

            $files[] = $file;
        }

        // Sort files by path for consistent output
        usort($files, function ($a, $b) {
            return $a->getRelativePathname() <=> $b->getRelativePathname();
        });

        // Generate text output with summary
        return $this->generateText($files);
    }

    /**
     * Generate plain text formatted output with tree structure and summary.
     *
     * @param array<int, SplFileInfo> $files
     */
    private function generateText(array $files): string
    {
        $output = '';

        // Calculate counts
        $totalFiles = count($files);
        $filesWithoutContent = count($this->filesWithoutContent);
        $filesWithContent = $totalFiles - $filesWithoutContent;

        // Add header
        $output .= $this->generateHeader($totalFiles, $filesWithContent, $filesWithoutContent);

        // Add file tree summary
        $output .= $this->generateFileTree($files);

        // Add file contents
        $output .= $this->generateFileContents($files, $filesWithContent, $filesWithoutContent);

        return $output;
    }

    /**
     * Generate header with metadata.
     */
    private function generateHeader(int $totalFiles, int $filesWithContent, int $filesWithoutContent): string
    {
        $output = "PROJECT SNAPSHOT\n";
        $output .= "================================================================================\n\n";
        $output .= 'Generated: '.date('Y-m-d H:i:s')."\n";
        $output .= "Total files in snapshot: {$totalFiles}\n";
        $output .= "  - With full content: {$filesWithContent}\n";
        $output .= "  - Referenced only (no content): {$filesWithoutContent}\n";
        $output .= "\n";
        $output .= "This snapshot is optimized for AI model consumption.\n";
        $output .= "Large vendor files are referenced in the structure but their content is omitted.\n";
        $output .= "\n";
        $output .= "================================================================================\n\n";

        return $output;
    }

    /**
     * Generate ASCII tree structure of all files.
     *
     * @param array<int, SplFileInfo> $files
     */
    private function generateFileTree(array $files): string
    {
        $output = "FILE STRUCTURE\n";
        $output .= "================================================================================\n\n";

        // Build tree structure
        /**
         * @var array<string, mixed> $tree
         */
        $tree = [];

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $parts = explode('/', $relativePath);

            // Build nested array
            $current = &$tree;
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        // Print tree
        $output .= $this->renderTree($tree, 0);
        $output .= "\n";
        $output .= "================================================================================\n\n";

        return $output;
    }

    /**
     * Recursively render tree structure.
     *
     * @param array<string, mixed> $tree
     */
    private function renderTree(array $tree, int $depth = 0): string
    {
        $output = '';
        $keys = array_keys($tree);
        sort($keys);

        foreach ($keys as $i => $key) {
            $isLast = ($i === count($keys) - 1);
            $prefix = str_repeat('  ', $depth);
            $connector = $isLast ? '└── ' : '├── ';

            $output .= $prefix.$connector.$key."\n";

            if (!empty($tree[$key]) && is_array($tree[$key])) {
                $output .= $this->renderTree($tree[$key], $depth + 1);
            }
        }

        return $output;
    }

    /**
     * Generate file contents section.
     *
     * @param array<int, SplFileInfo> $files
     */
    private function generateFileContents(array $files, int $filesWithContent, int $filesWithoutContent): string
    {
        $output = "FILE CONTENTS\n";
        $output .= "================================================================================\n\n";

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();

            // Check if we should skip content
            if (in_array($relativePath, $this->filesWithoutContent)) {
                $output .= "────────────────────────────────────────────────────────────────────────────────\n";
                $output .= "FILE: {$relativePath}\n";
                $output .= "[CONTENT OMITTED - File referenced for structure, large or vendor file]\n";
                $output .= "\n";
                continue;
            }

            $content = $this->getFileContent($file);

            // File header
            $output .= "────────────────────────────────────────────────────────────────────────────────\n";
            $output .= "FILE: {$relativePath}\n";
            $output .= "────────────────────────────────────────────────────────────────────────────────\n";
            $output .= "\n";

            // File content
            $output .= $content;

            // File footer
            $output .= "\n\n";
        }

        $output .= "================================================================================\n";
        $output .= "END OF SNAPSHOT\n";
        $output .= "Files with content: {$filesWithContent}\n";
        $output .= "Files referenced (no content): {$filesWithoutContent}\n";
        $output .= "================================================================================\n";

        return $output;
    }

    /**
     * Safely read file content.
     */
    private function getFileContent(SplFileInfo $file): string
    {
        try {
            $content = file_get_contents($file->getRealPath());

            return false !== $content ? $content : "// Error reading file\n";
        } catch (\Exception $e) {
            return '// Error reading file: '.$e->getMessage()."\n";
        }
    }
}
