<?php

declare(strict_types=1);

namespace App\Service;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;

/**
 * Service to handle language switching between different siteaccesses
 */
class LanguageSwitcherService
{
    public function __construct(
        private ContentService $contentService,
        private LocationService $locationService,
        private LocaleRoutingService $localeRoutingService,
        private SiteAccessServiceInterface $siteAccessService
    ) {
    }

    /**
     * Get all available language versions for content
     *
     * @return array<string, array{siteaccess: string, locale: string, market: string, displayName: string, url: string, available: bool, current: bool}>
     */
    public function getAvailableLanguages(Content $content, Location $location): array
    {
        $currentSiteaccess = $this->siteAccessService->getCurrent();
        $currentSiteaccessName = $currentSiteaccess ? $currentSiteaccess->name : 'global-en';
        $currentMarket = $this->localeRoutingService->getCurrentMarket($currentSiteaccessName);

        $languages = [];
        $allLocales = $this->localeRoutingService->getAllLocales();

        foreach ($allLocales as $locale) {
            $siteaccess = $this->localeRoutingService->getSiteaccessForLocale($locale);
            $market = $this->localeRoutingService->getCurrentMarket($siteaccess);

            // Check if content has this translation
            $hasTranslation = $this->hasTranslation($content, $locale);

            // Get URL for this language version
            $url = $this->getLanguageUrl($location, $siteaccess, $locale, $hasTranslation);

            $languages[$locale] = [
                'siteaccess' => $siteaccess,
                'locale' => $locale,
                'market' => $market,
                'displayName' => $this->localeRoutingService->getLocaleDisplayName($locale),
                'url' => $url,
                'available' => $hasTranslation,
                'current' => $siteaccess === $currentSiteaccessName,
            ];
        }

        return $languages;
    }

    /**
     * Get languages grouped by market
     *
     * @return array<string, array<string, array{siteaccess: string, locale: string, market: string, displayName: string, url: string, available: bool, current: bool}>>
     */
    public function getLanguagesByMarket(Content $content, Location $location): array
    {
        $allLanguages = $this->getAvailableLanguages($content, $location);
        $groupedLanguages = [];

        foreach ($allLanguages as $locale => $languageInfo) {
            $market = $languageInfo['market'];
            $groupedLanguages[$market][$locale] = $languageInfo;
        }

        return $groupedLanguages;
    }

    /**
     * Check if content has translation in specific language
     */
    public function hasTranslation(Content $content, string $languageCode): bool
    {
        $versionInfo = $content->getVersionInfo();

        return in_array($languageCode, $versionInfo->languageCodes, true);
    }

    /**
     * Get the best available translation for content in a specific locale
     */
    public function getTranslation(Content $content, string $languageCode): ?Content
    {
        if ($this->hasTranslation($content, $languageCode)) {
            return $this->contentService->loadContent(
                $content->id,
                [$languageCode]
            );
        }

        // Check fallback languages
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale($languageCode);
        $market = $this->localeRoutingService->getCurrentMarket($siteaccess);
        $fallbackLocales = $this->localeRoutingService->getMarketLocales($market);

        foreach ($fallbackLocales as $fallbackLocale) {
            if ($this->hasTranslation($content, $fallbackLocale)) {
                return $this->contentService->loadContent(
                    $content->id,
                    [$fallbackLocale]
                );
            }
        }

        return null;
    }

    /**
     * Get available markets for content
     *
     * @return array<string>
     */
    public function getAvailableMarkets(Content $content): array
    {
        $markets = [];
        $allMarkets = $this->localeRoutingService->getAllMarkets();

        foreach ($allMarkets as $market) {
            $marketLocales = $this->localeRoutingService->getMarketLocales($market);

            // Check if content has any translation in this market
            foreach ($marketLocales as $locale) {
                if ($this->hasTranslation($content, $locale)) {
                    $markets[] = $market;
                    break;
                }
            }
        }

        return $markets;
    }

    /**
     * Check if current content is using fallback language
     */
    public function isFallbackLanguage(Content $content, string $currentLocale): bool
    {
        $versionInfo = $content->getVersionInfo();
        $initialLanguageCode = $versionInfo->initialLanguageCode;

        return $initialLanguageCode !== $currentLocale && ! $this->hasTranslation($content, $currentLocale);
    }

    /**
     * Get the actual language code being displayed
     */
    public function getDisplayedLanguage(Content $content, string $requestedLocale): string
    {
        if ($this->hasTranslation($content, $requestedLocale)) {
            return $requestedLocale;
        }

        // Return the fallback language
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale($requestedLocale);
        $market = $this->localeRoutingService->getCurrentMarket($siteaccess);
        $fallbackLocales = $this->localeRoutingService->getMarketLocales($market);

        foreach ($fallbackLocales as $fallbackLocale) {
            if ($this->hasTranslation($content, $fallbackLocale)) {
                return $fallbackLocale;
            }
        }

        return $content->getVersionInfo()->initialLanguageCode;
    }

    /**
     * Get URL for content in specific language
     */
    private function getLanguageUrl(
        Location $location,
        string $siteaccess,
        string $locale,
        bool $hasTranslation
    ): string {
        $market = $this->localeRoutingService->getCurrentMarket($siteaccess);

        // Get the URL alias for the location
        $urlAlias = $location->pathString;

        // For translated content, use the actual URL
        // For non-translated content, use the fallback URL
        $path = $urlAlias;

        // Build full URL with locale prefix
        return $this->localeRoutingService->buildFullUrl($market, $locale, $path);
    }
}
