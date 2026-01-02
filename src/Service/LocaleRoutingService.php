<?php

declare(strict_types=1);

namespace App\Service;

use Ibexa\Bundle\Core\Routing\DefaultRouter;

/**
 * Service to handle locale-aware URL generation and routing
 */
class LocaleRoutingService
{
    private const MARKET_LOCALES = [
        'global' => ['eng-GB'],
        'my' => ['eng-MY', 'eng-GB'],
        'th' => ['eng-TH', 'tha-TH', 'eng-GB'],
    ];

    private const SITEACCESS_MARKETS = [
        'global-en' => 'global',
        'my-en' => 'my',
        'th-en' => 'th',
        'th-th' => 'th',
    ];

    private const LOCALE_SITEACCESS = [
        'eng-GB' => 'global-en',
        'eng-MY' => 'my-en',
        'eng-TH' => 'th-en',
        'tha-TH' => 'th-th',
    ];

    public function __construct(
        private DefaultRouter $router
    ) {
    }

    /**
     * Get current market from siteaccess
     */
    public function getCurrentMarket(string $siteaccessName): string
    {
        return self::SITEACCESS_MARKETS[$siteaccessName] ?? 'global';
    }

    /**
     * Get available locales for a market
     */
    public function getMarketLocales(string $market): array
    {
        return self::MARKET_LOCALES[$market] ?? ['eng-GB'];
    }

    /**
     * Get siteaccess name for a locale
     */
    public function getSiteaccessForLocale(string $locale): string
    {
        return self::LOCALE_SITEACCESS[$locale] ?? 'global-en';
    }

    /**
     * Generate URL for specific locale
     */
    public function generateLocaleUrl(string $routeName, string $locale, array $parameters = []): string
    {
        $siteaccess = $this->getSiteaccessForLocale($locale);

        $parameters['siteaccess'] = $siteaccess;

        return $this->router->generate($routeName, $parameters);
    }

    /**
     * Get URL prefix for siteaccess
     * Returns the URL prefix based on siteaccess name
     */
    public function getSiteaccessUrlPrefix(string $siteaccessName): string
    {
        return match ($siteaccessName) {
            'global-en' => '/global-en',
            'my-en' => '/my-en',
            'th-en' => '/th-en',
            'th-th' => '/th-th',
            'admin' => '/admin',
            default => '',
        };
    }

    /**
     * Get URL prefix for market
     */
    public function getMarketUrlPrefix(string $market): string
    {
        return match ($market) {
            'global' => '/global-en',
            'my' => '/my-en',
            'th' => '/th-en',
            default => '',
        };
    }

    /**
     * Get locale URL segment
     * In the new structure, all locales have their own siteaccess prefix, so no suffixes are needed
     */
    public function getLocaleUrlSuffix(string $locale): string
    {
        // No locale suffixes in new structure - each siteaccess has unique prefix
        return '';
    }

    /**
     * Build full URL with market and locale prefixes
     * Handles the complete URL structure: /siteaccess/path
     */
    public function buildFullUrl(string $market, string $locale, string $path): string
    {
        $siteaccess = $this->getSiteaccessForLocale($locale);
        $prefix = $this->getSiteaccessUrlPrefix($siteaccess);

        // Add path
        $path = trim($path, '/');
        if ($path) {
            return $prefix . '/' . $path;
        }

        return $prefix;
    }

    /**
     * Generate route URL with locale prefix
     * Automatically adds the correct locale prefix based on current siteaccess
     */
    public function generateRouteWithLocale(string $routeName, string $siteaccessName, array $parameters = []): string
    {
        $prefix = $this->getSiteaccessUrlPrefix($siteaccessName);

        // Generate base route
        $route = $this->router->generate($routeName, $parameters);

        // If route already starts with prefix, return as-is
        if ($prefix && str_starts_with($route, $prefix)) {
            return $route;
        }

        // Add prefix
        return $prefix . $route;
    }

    /**
     * Parse URL to extract market, locale, and siteaccess
     * Analyzes URL structure to determine the appropriate siteaccess
     */
    public function parseUrl(string $url): array
    {
        $market = 'global';
        $locale = 'eng-GB';
        $siteaccess = 'global-en';

        // Normalize URL
        $url = '/' . trim($url, '/');

        // Check for admin
        if (str_starts_with($url, '/admin')) {
            return [
                'market' => 'global',
                'locale' => 'eng-GB',
                'siteaccess' => 'admin',
            ];
        }

        // Check for Thailand Thai (/th-th)
        if (str_starts_with($url, '/th-th')) {
            $market = 'th';
            $locale = 'tha-TH';
            $siteaccess = 'th-th';
        }
        // Check for Thailand English (/th-en)
        elseif (str_starts_with($url, '/th-en')) {
            $market = 'th';
            $locale = 'eng-TH';
            $siteaccess = 'th-en';
        }
        // Check for Malaysia English (/my-en)
        elseif (str_starts_with($url, '/my-en')) {
            $market = 'my';
            $locale = 'eng-MY';
            $siteaccess = 'my-en';
        }
        // Check for Global English (/global-en)
        elseif (str_starts_with($url, '/global-en')) {
            $market = 'global';
            $locale = 'eng-GB';
            $siteaccess = 'global-en';
        }

        return [
            'market' => $market,
            'locale' => $locale,
            'siteaccess' => $siteaccess,
        ];
    }

    /**
     * Check if URL belongs to a specific siteaccess
     */
    public function matchesSiteaccess(string $url, string $siteaccessName): bool
    {
        $parsed = $this->parseUrl($url);
        return $parsed['siteaccess'] === $siteaccessName;
    }

    /**
     * Get URL path without locale prefix
     * Strips market and language segments from URL
     */
    public function stripLocalePrefix(string $url): string
    {
        $url = '/' . trim($url, '/');

        // Strip admin prefix
        if (str_starts_with($url, '/admin')) {
            return substr($url, 6) ?: '/';
        }

        // Strip siteaccess prefixes (longest match first)
        $prefixes = [
            '/global-en',
            '/th-th',
            '/th-en',
            '/my-en',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($url, $prefix)) {
                return substr($url, strlen($prefix)) ?: '/';
            }
        }

        return $url;
    }

    /**
     * Get all available markets
     */
    public function getAllMarkets(): array
    {
        return array_keys(self::MARKET_LOCALES);
    }

    /**
     * Get all locale codes
     */
    public function getAllLocales(): array
    {
        return array_keys(self::LOCALE_SITEACCESS);
    }

    /**
     * Get locale display name
     */
    public function getLocaleDisplayName(string $locale): string
    {
        return match ($locale) {
            'eng-GB' => 'English (Global)',
            'eng-MY' => 'English (Malaysia)',
            'eng-TH' => 'English (Thailand)',
            'tha-TH' => 'ภาษาไทย (Thai)',
            default => $locale,
        };
    }
}
