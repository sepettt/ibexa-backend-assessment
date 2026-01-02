<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\UrlAliasGenerator;
use Ibexa\Contracts\Core\Repository\Events\Content\PublishVersionEvent;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that automatically generates URL aliases when content is published.
 *
 * This subscriber listens to content publish events and uses the UrlAliasGenerator
 * service to create SEO-friendly URL aliases based on content type and fields.
 *
 * Features:
 * - Automatic alias generation on publish
 * - Support for market-specific URL prefixes
 * - Virtual segment handling (/business-units, /industries)
 * - Locale-aware URL generation
 */
class UrlAliasEventSubscriber implements EventSubscriberInterface
{
    /**
     * Content types that should have URL aliases generated.
     */
    private const ALIASED_CONTENT_TYPES = [
        'news',
        'insights',
        'landing_page',
    ];

    private UrlAliasGenerator $urlAliasGenerator;

    private URLAliasService $urlAliasService;

    public function __construct(
        UrlAliasGenerator $urlAliasGenerator,
        URLAliasService $urlAliasService
    ) {
        $this->urlAliasGenerator = $urlAliasGenerator;
        $this->urlAliasService = $urlAliasService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PublishVersionEvent::class => ['onPublishVersion', 0],
        ];
    }

    /**
     * Handle content publish event and generate URL alias.
     *
     * @param PublishVersionEvent $event The publish event
     */
    public function onPublishVersion(PublishVersionEvent $event): void
    {
        $content = $event->getContent();
        $contentType = $content->getContentType();

        // Only process configured content types
        if (! in_array($contentType->identifier, self::ALIASED_CONTENT_TYPES, true)) {
            return;
        }

        // Generate and create URL alias
        $this->createUrlAlias($content);
    }

    /**
     * Create a URL alias for the given content.
     *
     * @param Content $content The content to create alias for
     */
    private function createUrlAlias(Content $content): void
    {
        try {
            $contentType = $content->getContentType()->identifier;
            $mainLanguageCode = $content->contentInfo->mainLanguageCode;

            // Get market from language code (e.g., 'en-MY' -> 'my')
            $market = $this->extractMarket($mainLanguageCode);

            // Generate the URL path based on content type
            $urlPath = $this->generateUrlPath($content, $contentType, $market);

            if (empty($urlPath)) {
                return;
            }

            // Create the URL alias
            $location = $content->contentInfo->getMainLocation();
            if ($location === null) {
                return;
            }

            // Check if alias already exists
            $existingAliases = $this->urlAliasService->listLocationAliases(
                $location,
                false,
                $mainLanguageCode
            );

            // Only create if no custom alias exists yet
            $hasCustomAlias = false;
            foreach ($existingAliases as $alias) {
                if ($alias->isCustom && $alias->path === $urlPath) {
                    $hasCustomAlias = true;
                    break;
                }
            }

            if (! $hasCustomAlias) {
                $this->urlAliasService->createUrlAlias(
                    $location,
                    $urlPath,
                    $mainLanguageCode,
                    false, // not forwarding
                    true   // always available
                );
            }
        } catch (\Exception $e) {
            // Log error but don't break the publish process
            // In production, use proper logger
            error_log(sprintf(
                'Failed to create URL alias for content %d: %s',
                $content->id,
                $e->getMessage()
            ));
        }
    }

    /**
     * Generate URL path based on content type and market.
     *
     * @param Content $content The content
     * @param string $contentType The content type identifier
     * @param string $market The market code
     *
     * @return string The generated URL path
     */
    private function generateUrlPath(Content $content, string $contentType, string $market): string
    {
        // Get the title field value
        $title = '';
        foreach (['title', 'name', 'heading'] as $fieldIdentifier) {
            if ($content->getField($fieldIdentifier)) {
                $title = (string) $content->getFieldValue($fieldIdentifier);
                break;
            }
        }

        if (empty($title)) {
            return '';
        }

        // Generate slug from title
        $slug = $this->urlAliasGenerator->slugify($title);

        // Build path with market prefix and virtual segments
        $pathParts = [];

        // Add market prefix for non-global markets
        if ($market !== 'global') {
            $pathParts[] = $market;
        }

        // Add virtual segment based on content type
        $virtualSegment = $this->getVirtualSegment($contentType);
        if ($virtualSegment !== null) {
            $pathParts[] = $virtualSegment;
        }

        // Add the slug
        $pathParts[] = $slug;

        return '/' . implode('/', $pathParts);
    }

    /**
     * Get virtual segment for content type.
     *
     * @param string $contentType The content type identifier
     *
     * @return string|null The virtual segment or null
     */
    private function getVirtualSegment(string $contentType): ?string
    {
        return match ($contentType) {
            'news' => 'news',
            'insights' => 'insights',
            default => null,
        };
    }

    /**
     * Extract market code from language code.
     *
     * @param string $languageCode Language code (e.g., 'en-MY', 'th-TH')
     *
     * @return string Market code (e.g., 'my', 'th', 'global')
     */
    private function extractMarket(string $languageCode): string
    {
        $parts = explode('-', $languageCode);

        if (count($parts) < 2) {
            return 'global';
        }

        $countryCode = strtolower($parts[1]);

        return match ($countryCode) {
            'my' => 'my',
            'th' => 'th',
            default => 'global',
        };
    }
}
