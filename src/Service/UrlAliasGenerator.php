<?php

declare(strict_types=1);

namespace App\Service;

use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;

/**
 * Service to generate deterministic URLs based on content structure.
 * Implements virtual segment logic for SEO-friendly URLs.
 *
 * Virtual segments are URL parts that don't directly map to content tree structure
 * but are determined by content type (e.g., /news/, /insights/).
 */
class UrlAliasGenerator
{
    /**
     * Market-specific URL prefixes (virtual segments for markets)
     */
    private const MARKET_URL_PATTERNS = [
        'sg' => '/sg',
        'my' => '/my',
        'global' => '',
    ];

    /**
     * Content type virtual segments
     * These are URL patterns that don't correspond to actual locations
     */
    private const CONTENT_TYPE_PATTERNS = [
        'news' => 'news',
        'insights' => 'insights',
        'landing_page' => '',
        'redirect' => '',
    ];

    /**
     * Content types that should use virtual segments in URLs
     */
    private const VIRTUAL_SEGMENT_TYPES = [
        'news',
        'insights',
    ];

    public function __construct(
        private URLAliasService $urlAliasService
    ) {
    }

    /**
     * Generate URL alias for content based on market and content type.
     * Uses virtual segments for content type patterns.
     *
     * Examples:
     * - News in Singapore: /sg/news/article-title
     * - Insights in Malaysia: /my/insights/article-title
     * - Landing page globally: /page-title
     *
     * @param Content $content The content object
     * @param Location $location The location object
     * @param string $market Market identifier (sg, my, global)
     * @return string The generated URL alias
     */
    public function generateUrlAlias(Content $content, Location $location, string $market = 'global'): string
    {
        $contentType = $content->getContentType()->identifier;

        // Get market prefix (virtual segment for market)
        $marketPrefix = self::MARKET_URL_PATTERNS[$market] ?? '';

        // Get content type pattern (virtual segment for content type)
        $typePattern = self::CONTENT_TYPE_PATTERNS[$contentType] ?? '';

        // Build URL components
        $components = array_filter([
            $marketPrefix,
            $typePattern,
            $this->generateSlug($content),
        ]);

        return '/' . implode('/', $components);
    }

    /**
     * Check if a content type uses virtual segments
     */
    public function usesVirtualSegments(string $contentTypeIdentifier): bool
    {
        return in_array($contentTypeIdentifier, self::VIRTUAL_SEGMENT_TYPES, true);
    }

    /**
     * Get the virtual segment for a content type
     */
    public function getVirtualSegment(string $contentTypeIdentifier): ?string
    {
        if (! $this->usesVirtualSegments($contentTypeIdentifier)) {
            return null;
        }

        return self::CONTENT_TYPE_PATTERNS[$contentTypeIdentifier] ?? null;
    }

    /**
     * Generate full URL path with virtual segments.
     * This method constructs the complete URL including all virtual segments.
     *
     * @param Content $content Content object
     * @param string $market Market identifier
     * @param string|null $languageCode Language code for locale-specific URLs
     * @return string Complete URL path
     */
    public function generateFullPath(Content $content, string $market = 'global', ?string $languageCode = null): string
    {
        $contentType = $content->getContentType()->identifier;
        $segments = [];

        // Add market prefix (virtual segment)
        if ($market !== 'global' && isset(self::MARKET_URL_PATTERNS[$market])) {
            $segments[] = trim(self::MARKET_URL_PATTERNS[$market], '/');
        }

        // Add language segment if needed (for my/bm, my/eng)
        if ($languageCode && $market === 'my') {
            $langSegment = $this->getLanguageSegment($languageCode);
            if ($langSegment) {
                $segments[] = $langSegment;
            }
        }

        // Add content type virtual segment
        if ($this->usesVirtualSegments($contentType)) {
            $segments[] = self::CONTENT_TYPE_PATTERNS[$contentType];
        }

        // Add slug
        $segments[] = $this->generateSlug($content);

        return '/' . implode('/', array_filter($segments));
    }

    /**
     * Convert string to URL-friendly slug
     */
    public function slugify(string $text): string
    {
        // Replace non-letter or digits by hyphens
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim
        $text = trim($text, '-');

        // Remove duplicate hyphens
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Get URL pattern for content type
     */
    public function getContentTypePattern(string $contentTypeIdentifier): string
    {
        return self::CONTENT_TYPE_PATTERNS[$contentTypeIdentifier] ?? '';
    }

    /**
     * Get all market prefixes
     */
    public function getMarketPrefixes(): array
    {
        return self::MARKET_URL_PATTERNS;
    }

    /**
     * Extract language segment from language code
     */
    private function getLanguageSegment(string $languageCode): ?string
    {
        // Map language codes to URL segments
        $languageSegments = [
            'may-MY' => 'bm',
            'eng-MY' => 'eng',
        ];

        return $languageSegments[$languageCode] ?? null;
    }

    /**
     * Generate URL slug from content title
     */
    private function generateSlug(Content $content): string
    {
        $title = '';

        // Try to get title field
        if (method_exists($content, 'hasField') && $content->hasField('title')) {
            $title = $content->getFieldValue('title')->text ?? '';
        } elseif (method_exists($content, 'hasField') && $content->hasField('name')) {
            $title = $content->getFieldValue('name')->text ?? '';
        }

        // Fallback to content name
        if (empty($title)) {
            $title = $content->getName();
        }

        return $this->slugify($title);
    }
}
