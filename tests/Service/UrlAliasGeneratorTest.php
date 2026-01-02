<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\UrlAliasGenerator;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use PHPUnit\Framework\TestCase;

class UrlAliasGeneratorTest extends TestCase
{
    private UrlAliasGenerator $generator;

    private URLAliasService $urlAliasService;

    protected function setUp(): void
    {
        $this->urlAliasService = $this->createMock(URLAliasService::class);
        $this->generator = new UrlAliasGenerator($this->urlAliasService);
    }

    public function testSlugifyConvertsTextToUrlFriendlySlug(): void
    {
        $this->assertEquals('hello-world', $this->generator->slugify('Hello World'));
        $this->assertEquals('hello-world', $this->generator->slugify('Hello  World'));
        $this->assertEquals('hello-world', $this->generator->slugify('  Hello World  '));
    }

    public function testSlugifyRemovesSpecialCharacters(): void
    {
        $this->assertEquals('hello-world', $this->generator->slugify('Hello @#$ World!'));
        $this->assertEquals('test-123', $this->generator->slugify('Test 123'));
        $this->assertEquals('my-test-article', $this->generator->slugify('My Test Article!!!'));
    }

    public function testSlugifyHandlesMultipleHyphens(): void
    {
        $this->assertEquals('hello-world', $this->generator->slugify('Hello---World'));
        $this->assertEquals('test-case', $this->generator->slugify('Test - - Case'));
    }

    public function testSlugifyConvertsToLowercase(): void
    {
        $this->assertEquals('uppercase-text', $this->generator->slugify('UPPERCASE TEXT'));
        $this->assertEquals('mixedcase', $this->generator->slugify('MixedCase'));
    }

    public function testSlugifyHandlesEmptyString(): void
    {
        $this->assertEquals('n-a', $this->generator->slugify(''));
        $this->assertEquals('n-a', $this->generator->slugify('   '));
    }

    public function testSlugifyHandlesSpecialCharactersOnly(): void
    {
        $this->assertEquals('n-a', $this->generator->slugify('!@#$%^&*()'));
        $this->assertEquals('n-a', $this->generator->slugify('---'));
    }

    public function testSlugifyPreservesNumbers(): void
    {
        $this->assertEquals('article-2024', $this->generator->slugify('Article 2024'));
        $this->assertEquals('123-test', $this->generator->slugify('123 Test'));
    }

    public function testSlugifyHandlesUnicodeCharacters(): void
    {
        // This tests transliteration
        $result = $this->generator->slugify('Café');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $result);
        $this->assertNotEmpty($result);
    }

    public function testSlugifyHandlesLongText(): void
    {
        $longText = 'This is a very long article title that should be converted to a slug';
        $result = $this->generator->slugify($longText);

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $result);
        $this->assertEquals('this-is-a-very-long-article-title-that-should-be-converted-to-a-slug', $result);
    }

    public function testSlugifyHandlesCamelCase(): void
    {
        $this->assertEquals('thisiscamelcase', $this->generator->slugify('thisIsCamelCase'));
    }

    public function testSlugifyHandlesAmpersands(): void
    {
        $this->assertEquals('rock-roll', $this->generator->slugify('Rock & Roll'));
        $this->assertEquals('this-that', $this->generator->slugify('This & That'));
    }

    public function testSlugifyHandlesQuotes(): void
    {
        $this->assertEquals('don-t-stop', $this->generator->slugify("Don't Stop"));
        $this->assertEquals('hello-world', $this->generator->slugify('"Hello World"'));
    }

    public function testSlugifyHandlesParentheses(): void
    {
        $this->assertEquals('test-case', $this->generator->slugify('Test (Case)'));
        $this->assertEquals('article-2024', $this->generator->slugify('Article (2024)'));
    }

    public function testSlugifyHandlesSlashes(): void
    {
        $this->assertEquals('and-or', $this->generator->slugify('And/Or'));
        $this->assertEquals('path-to-file', $this->generator->slugify('Path/To/File'));
    }

    public function testSlugifyHandlesUnderscores(): void
    {
        $this->assertEquals('hello-world', $this->generator->slugify('Hello_World'));
        $this->assertEquals('test-case', $this->generator->slugify('test_case'));
    }

    public function testSlugifyHandlesDots(): void
    {
        $this->assertEquals('mr-smith', $this->generator->slugify('Mr. Smith'));
        $this->assertEquals('version-2-0', $this->generator->slugify('Version 2.0'));
    }

    public function testSlugifyHandlesCommas(): void
    {
        $this->assertEquals('first-second-third', $this->generator->slugify('First, Second, Third'));
    }

    public function testSlugifyHandlesColons(): void
    {
        $this->assertEquals('title-subtitle', $this->generator->slugify('Title: Subtitle'));
    }

    public function testSlugifyHandlesSemicolons(): void
    {
        $this->assertEquals('part-one-part-two', $this->generator->slugify('Part One; Part Two'));
    }

    public function testSlugifyHandlesLeadingNumbers(): void
    {
        $this->assertEquals('2024-trends', $this->generator->slugify('2024 Trends'));
        $this->assertEquals('100-ways', $this->generator->slugify('100 Ways'));
    }

    public function testGetContentTypePatternReturnsCorrectPattern(): void
    {
        $this->assertEquals('news', $this->generator->getContentTypePattern('news'));
        $this->assertEquals('insights', $this->generator->getContentTypePattern('insights'));
        $this->assertEquals('', $this->generator->getContentTypePattern('landing_page'));
        $this->assertEquals('', $this->generator->getContentTypePattern('unknown_type'));
    }

    public function testGetMarketPrefixesReturnsAllMarkets(): void
    {
        $prefixes = $this->generator->getMarketPrefixes();

        $this->assertIsArray($prefixes);
        $this->assertArrayHasKey('sg', $prefixes);
        $this->assertArrayHasKey('my', $prefixes);
        $this->assertArrayHasKey('global', $prefixes);
        $this->assertEquals('/sg', $prefixes['sg']);
        $this->assertEquals('/my', $prefixes['my']);
        $this->assertEquals('', $prefixes['global']);
    }

    public function testGetContentTypePatternForAllKnownTypes(): void
    {
        $this->assertEquals('news', $this->generator->getContentTypePattern('news'));
        $this->assertEquals('insights', $this->generator->getContentTypePattern('insights'));
        $this->assertEquals('', $this->generator->getContentTypePattern('landing_page'));
    }

    public function testGetMarketPrefixesContainsExpectedKeys(): void
    {
        $prefixes = $this->generator->getMarketPrefixes();

        $this->assertCount(3, $prefixes);
        $this->assertArrayHasKey('sg', $prefixes);
        $this->assertArrayHasKey('my', $prefixes);
        $this->assertArrayHasKey('global', $prefixes);
    }

    public function testUsesVirtualSegmentsForNewsContentType(): void
    {
        $this->assertTrue($this->generator->usesVirtualSegments('news'));
    }

    public function testUsesVirtualSegmentsForInsightsContentType(): void
    {
        $this->assertTrue($this->generator->usesVirtualSegments('insights'));
    }

    public function testDoesNotUseVirtualSegmentsForLandingPage(): void
    {
        $this->assertFalse($this->generator->usesVirtualSegments('landing_page'));
    }

    public function testGetVirtualSegmentForNews(): void
    {
        $this->assertEquals('news', $this->generator->getVirtualSegment('news'));
    }

    public function testGetVirtualSegmentForInsights(): void
    {
        $this->assertEquals('insights', $this->generator->getVirtualSegment('insights'));
    }

    public function testGetVirtualSegmentReturnsNullForLandingPage(): void
    {
        $this->assertNull($this->generator->getVirtualSegment('landing_page'));
    }

    public function testGetVirtualSegmentReturnsNullForUnknownType(): void
    {
        $this->assertNull($this->generator->getVirtualSegment('unknown_type'));
    }
}
