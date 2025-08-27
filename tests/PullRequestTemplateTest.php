<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * PullRequestTemplateTest
 *
 * These tests validate the repository's Pull Request template to ensure
 * contributors have consistent guidance. They check for:
 *  - Presence of the template file in conventional locations
 *  - Required section headings (Description, Related Issue, Changes, Testing, Checklist)
 *  - Presence of guidance comments under specific sections
 *  - Required checklist items
 *
 * Note: Testing library/framework: PHPUnit.
 */
final class PullRequestTemplateTest extends TestCase
{
    /** @var string[] */
    private array $candidatePaths = [
        '.github/pull_request_template.md',
        '.github/PULL_REQUEST_TEMPLATE.md',
        'docs/pull_request_template.md',
        'PULL_REQUEST_TEMPLATE.md',
        'pull_request_template.md',
    ];

    /** Locate the PR template file in the repository. */
    private function locateTemplatePath(): ?string
    {
        foreach ($this->candidatePaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        // Fallback: if the file under test was placed elsewhere, attempt a recursive search.
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.',
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO));
        foreach ($iterator as $file) {
            $name = strtolower($file->getFilename());
            if (strpos($name, 'pull_request_template') !== false && $file->isFile()) {
                return $file->getPathname();
            }
        }
        return null;
    }

    /** Ensure the PR template file exists somewhere expected. */
    public function test_template_file_exists(): void
    {
        $path = $this->locateTemplatePath();
        $this->assertNotNull($path, 'Pull Request template file was not found in common locations.');
        $this->assertFileExists($path);
    }

    /** Read template contents safely. */
    private function readTemplate(): string
    {
        $path = $this->locateTemplatePath();
        $this->assertNotNull($path, 'Pull Request template file was not found.');
        $content = @file_get_contents($path);
        $this->assertIsString($content, 'Unable to read Pull Request template content.');
        return (string)$content;
    }

    /** Provides required section headings. */
    public static function requiredHeadingsProvider(): array
    {
        return [
            ['## Description'],
            ['## Related Issue'],
            ['## Changes'],
            ['## Testing'],
            ['## Checklist'],
        ];
    }

    /**
     * Each required heading should be present, exactly once or at least once.
     * If multiple templates exist, this test focuses on the located file.
     * @dataProvider requiredHeadingsProvider
     */
    public function test_required_headings_are_present(string $heading): void
    {
        $content = $this->readTemplate();
        $this->assertStringContainsString($heading, $content, "Missing required heading: {$heading}");
    }

    /** Guidance comments should be present under specific sections where applicable. */
    public function test_guidance_comments_exist_for_description_related_issue_and_testing(): void
    {
        $content = $this->readTemplate();

        $this->assertMatchesRegularExpression(
            '/##\s*Description\s*\R<!--\s*Briefly describe what this PR does\s*-->/i',
            $content,
            'Description section should include guidance comment.'
        );

        $this->assertMatchesRegularExpression(
            '/##\s*Related Issue\s*\R<!--\s*Link related issues,\s*e\.g\.\s*Closes\s*#\d+\s*-->/i',
            $content,
            'Related Issue section should include guidance comment with example (e.g., Closes #123).'
        );

        $this->assertMatchesRegularExpression(
            '/##\s*Testing\s*\R<!--\s*How can reviewers test this\?\s*-->/i',
            $content,
            'Testing section should include guidance comment asking how reviewers can test.'
        );
    }

    /** Changes section should include a bullet list starter or placeholder. */
    public function test_changes_section_has_bullet_placeholder(): void
    {
        $content = $this->readTemplate();
        // Look for "## Changes" followed by at least one list marker ("- ")
        $this->assertMatchesRegularExpression(
            '/##\s*Changes\s*\R(?:-|\*\s)/',
            $content,
            'Changes section should include at least one bullet placeholder.'
        );
    }

    /** Checklist items should include WordPress testing, coding standards, and documentation. */
    public function test_checklist_contains_required_items(): void
    {
        $content = $this->readTemplate();

        $this->assertMatchesRegularExpression(
            '/- \[ \]\s*Tested on a WordPress site/i',
            $content,
            'Checklist should include "Tested on a WordPress site".'
        );

        $this->assertMatchesRegularExpression(
            '/- \[ \]\s*Follows WordPress coding standards/i',
            $content,
            'Checklist should include "Follows WordPress coding standards".'
        );

        $this->assertMatchesRegularExpression(
            '/- \[ \]\s*Documentation updated\s*\(if needed\)/i',
            $content,
            'Checklist should include "Documentation updated (if needed)".'
        );
    }

    /** Headings should use proper Markdown level (##). */
    public function test_heading_levels_are_h2(): void
    {
        $content = $this->readTemplate();

        // Ensure each required title uses "## " specifically (not # or ###)
        $required = [
            'Description',
            'Related Issue',
            'Changes',
            'Testing',
            'Checklist',
        ];

        foreach ($required as $title) {
            $this->assertMatchesRegularExpression(
                '/^\s*##\s+' . preg_quote($title, '/') . '\s*$/m',
                $content,
                sprintf('Heading "%s" should be level 2 (##).', $title)
            );
        }
    }

    /** Basic formatting hygiene: no Windows CRLF, ends with newline. */
    public function test_basic_formatting_hygiene(): void
    {
        $content = $this->readTemplate();

        // Disallow CRLF (\r\n) line endings to keep diffs clean (optional, but commonly enforced)
        $this->assertDoesNotMatchRegularExpression(
            "/\r\n/",
            $content,
            'PR template should use LF line endings.'
        );

        // Ensure file ends with a newline (POSIX style)
        $this->assertTrue(
            str_ends_with($content, "\n"),
            'PR template should end with a newline.'
        );
    }
}