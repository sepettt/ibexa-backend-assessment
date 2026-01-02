<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\LanguageSwitcherService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for language switcher functionality
 */
class LanguageSwitcherExtension extends AbstractExtension
{
    public function __construct(
        private LanguageSwitcherService $languageSwitcherService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_available_languages', [$this, 'getAvailableLanguages']),
            new TwigFunction('get_languages_by_market', [$this, 'getLanguagesByMarket']),
            new TwigFunction('has_translation', [$this, 'hasTranslation']),
            new TwigFunction('is_fallback_language', [$this, 'isFallbackLanguage']),
            new TwigFunction('get_displayed_language', [$this, 'getDisplayedLanguage']),
        ];
    }

    /**
     * Get all available languages for content
     */
    public function getAvailableLanguages(Content $content, Location $location): array
    {
        return $this->languageSwitcherService->getAvailableLanguages($content, $location);
    }

    /**
     * Get languages grouped by market
     */
    public function getLanguagesByMarket(Content $content, Location $location): array
    {
        return $this->languageSwitcherService->getLanguagesByMarket($content, $location);
    }

    /**
     * Check if content has translation
     */
    public function hasTranslation(Content $content, string $languageCode): bool
    {
        return $this->languageSwitcherService->hasTranslation($content, $languageCode);
    }

    /**
     * Check if using fallback language
     */
    public function isFallbackLanguage(Content $content, string $currentLocale): bool
    {
        return $this->languageSwitcherService->isFallbackLanguage($content, $currentLocale);
    }

    /**
     * Get displayed language code
     */
    public function getDisplayedLanguage(Content $content, string $requestedLocale): string
    {
        return $this->languageSwitcherService->getDisplayedLanguage($content, $requestedLocale);
    }
}
