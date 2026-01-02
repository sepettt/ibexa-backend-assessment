<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for URL alias path stability.
 * Ensures that generated URL aliases remain consistent and stable.
 */
class UrlAliasStabilityTest extends WebTestCase
{
    public function testUrlAliasGeneratorServiceIsAvailable(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $urlAliasGenerator = $container->get('App\Service\UrlAliasGenerator');
        $this->assertNotNull($urlAliasGenerator, 'UrlAliasGenerator should be available');
    }

    public function testSlugifyProducesSameOutputForSameInput(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $input = 'Breaking News Story';

        // Generate slug multiple times
        $slug1 = $generator->slugify($input);
        $slug2 = $generator->slugify($input);
        $slug3 = $generator->slugify($input);

        // Should be identical
        $this->assertEquals($slug1, $slug2, 'Slugify should be deterministic');
        $this->assertEquals($slug2, $slug3, 'Slugify should be deterministic');
        $this->assertEquals('breaking-news-story', $slug1);
    }

    public function testSlugifyIsIdempotent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $input = 'Test Article';
        $slug = $generator->slugify($input);

        // Applying slugify again to the slug should not change it
        $slugOfSlug = $generator->slugify($slug);

        $this->assertEquals($slug, $slugOfSlug, 'Slugify should be idempotent');
    }

    public function testSlugifyHandlesCaseConsistently(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        // Different cases should produce same slug
        $slug1 = $generator->slugify('Breaking News');
        $slug2 = $generator->slugify('BREAKING NEWS');
        $slug3 = $generator->slugify('breaking news');
        $slug4 = $generator->slugify('BrEaKiNg NeWs');

        $this->assertEquals($slug1, $slug2, 'Case should not affect slug');
        $this->assertEquals($slug2, $slug3, 'Case should not affect slug');
        $this->assertEquals($slug3, $slug4, 'Case should not affect slug');
        $this->assertEquals('breaking-news', $slug1);
    }

    public function testSlugifyHandlesWhitespaceConsistently(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        // Different whitespace should produce same slug
        $slug1 = $generator->slugify('Test  Article');
        $slug2 = $generator->slugify('Test   Article');
        $slug3 = $generator->slugify('Test    Article');
        $slug4 = $generator->slugify('  Test Article  ');

        $this->assertEquals('test-article', $slug1);
        $this->assertEquals($slug1, $slug2, 'Whitespace amount should not affect slug');
        $this->assertEquals($slug2, $slug3, 'Whitespace amount should not affect slug');
        $this->assertEquals($slug3, $slug4, 'Leading/trailing whitespace should not affect slug');
    }

    public function testSlugifyHandlesSpecialCharactersConsistently(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        // Special characters should be handled consistently
        $slug1 = $generator->slugify('Test & Article');
        $slug2 = $generator->slugify('Test & Article');
        $slug3 = $generator->slugify('Test&Article');

        $this->assertEquals($slug1, $slug2, 'Special characters should be handled consistently');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug1, 'Slug should only contain lowercase, numbers, and hyphens');
    }

    public function testSlugifyProducesUrlSafeOutput(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $inputs = [
            'Breaking News!',
            'Test@Article',
            'News #1',
            'Article (2024)',
            'Test/Article',
            'Article: Subtitle',
            'Test, Article',
        ];

        foreach ($inputs as $input) {
            $slug = $generator->slugify($input);

            // Should only contain URL-safe characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+$/',
                $slug,
                "Slug '{$slug}' should be URL-safe (from input '{$input}')"
            );

            // Should not have consecutive hyphens
            $this->assertStringNotContainsString('--', $slug, 'Should not have consecutive hyphens');

            // Should not start or end with hyphen
            $this->assertStringStartsNotWith('-', $slug, 'Should not start with hyphen');
            $this->assertStringEndsNotWith('-', $slug, 'Should not end with hyphen');
        }
    }

    public function testContentTypePatternIsStable(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        // Get patterns multiple times
        $pattern1 = $generator->getContentTypePattern('news');
        $pattern2 = $generator->getContentTypePattern('news');

        $this->assertEquals($pattern1, $pattern2, 'Content type pattern should be stable');
        $this->assertEquals('news', $pattern1);
    }

    public function testMarketPrefixesAreStable(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        // Get prefixes multiple times
        $prefixes1 = $generator->getMarketPrefixes();
        $prefixes2 = $generator->getMarketPrefixes();

        $this->assertEquals($prefixes1, $prefixes2, 'Market prefixes should be stable');

        // Verify structure
        $this->assertArrayHasKey('sg', $prefixes1);
        $this->assertArrayHasKey('my', $prefixes1);
        $this->assertArrayHasKey('global', $prefixes1);

        // Verify values are consistent
        $this->assertEquals('/sg', $prefixes1['sg']);
        $this->assertEquals('/my', $prefixes1['my']);
        $this->assertEquals('', $prefixes1['global']);
    }

    public function testSlugifyDoesNotProduceEmptyStrings(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $problematicInputs = [
            '',
            '   ',
            '!!!',
            '@@@',
            '---',
            '...',
        ];

        foreach ($problematicInputs as $input) {
            $slug = $generator->slugify($input);

            $this->assertNotEmpty($slug, "Should not produce empty slug from input '{$input}'");
            $this->assertEquals('n-a', $slug, 'Should produce default slug for invalid input');
        }
    }

    public function testSlugifyMaintainsStabilityWithUnicode(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $unicodeInputs = [
            'Café',
            'naïve',
            'résumé',
        ];

        foreach ($unicodeInputs as $input) {
            $slug1 = $generator->slugify($input);
            $slug2 = $generator->slugify($input);

            $this->assertEquals($slug1, $slug2, "Unicode handling should be stable for '{$input}'");
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug1, 'Unicode should be transliterated');
        }
    }

    public function testSlugifyStabilityWithNumbers(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $input = 'Article 123';

        // Numbers should be preserved consistently
        $slug1 = $generator->slugify($input);
        $slug2 = $generator->slugify($input);

        $this->assertEquals($slug1, $slug2);
        $this->assertEquals('article-123', $slug1);
        $this->assertStringContainsString('123', $slug1, 'Numbers should be preserved');
    }

    public function testPathStabilityAcrossMultipleGenerations(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $testCases = [
            'Breaking News Story',
            'Industry Analysis Report',
            'Singapore Market Update',
            'Malaysia Business News',
            'Global Economic Trends',
        ];

        foreach ($testCases as $title) {
            $slugs = [];

            // Generate slug 10 times
            for ($i = 0; $i < 10; $i++) {
                $slugs[] = $generator->slugify($title);
            }

            // All should be identical
            $unique = array_unique($slugs);
            $this->assertCount(1, $unique, "Slug for '{$title}' should be stable across multiple generations");
        }
    }

    public function testSlugifyHandlesHyphensBoundaries(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $generator = $container->get('App\Service\UrlAliasGenerator');

        $inputs = [
            '-test',
            'test-',
            '-test-',
            '--test',
            'test--',
        ];

        foreach ($inputs as $input) {
            $slug = $generator->slugify($input);

            // Should not start or end with hyphen
            $this->assertStringStartsNotWith('-', $slug, "Slug should not start with hyphen: '{$input}'");
            $this->assertStringEndsNotWith('-', $slug, "Slug should not end with hyphen: '{$input}'");

            // Should not have double hyphens
            $this->assertStringNotContainsString('--', $slug, "Slug should not have double hyphens: '{$input}'");
        }
    }
}
