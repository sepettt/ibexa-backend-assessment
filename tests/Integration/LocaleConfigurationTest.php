<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\LocaleRoutingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration tests to verify all 4 locales are configured and working correctly
 */
class LocaleConfigurationTest extends KernelTestCase
{
    private LocaleRoutingService $localeRoutingService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->localeRoutingService = $container->get(LocaleRoutingService::class);
    }

    public function testAllFourLocalesAreConfigured(): void
    {
        $allLocales = $this->localeRoutingService->getAllLocales();

        $this->assertCount(4, $allLocales, 'Should have exactly 4 locales configured');
        $this->assertContains('eng-GB', $allLocales);
        $this->assertContains('eng-TH', $allLocales);
        $this->assertContains('eng-MY', $allLocales);
        $this->assertContains('tha-TH', $allLocales);
    }

    public function testAllThreeMarketsAreConfigured(): void
    {
        $allMarkets = $this->localeRoutingService->getAllMarkets();

        $this->assertCount(3, $allMarkets, 'Should have exactly 3 markets configured');
        $this->assertContains('global', $allMarkets);
        $this->assertContains('th', $allMarkets);
        $this->assertContains('my', $allMarkets);
    }

    public function testGlobalMarketConfiguration(): void
    {
        $locales = $this->localeRoutingService->getMarketLocales('global');

        $this->assertCount(1, $locales, 'Global market should have 1 locale');
        $this->assertContains('eng-GB', $locales);
    }

    public function testThailandMarketConfiguration(): void
    {
        $locales = $this->localeRoutingService->getMarketLocales('th');

        $this->assertCount(3, $locales, 'Thailand market should have 3 locales (with fallback chain)');
        $this->assertContains('eng-TH', $locales);
        $this->assertContains('tha-TH', $locales);
        $this->assertContains('eng-GB', $locales);
    }

    public function testMalaysiaMarketConfiguration(): void
    {
        $locales = $this->localeRoutingService->getMarketLocales('my');

        $this->assertCount(2, $locales, 'Malaysia market should have 2 locales (with fallback)');
        $this->assertContains('eng-MY', $locales);
        $this->assertContains('eng-GB', $locales);
    }

    public function testGlobalEnglishSiteaccessMapping(): void
    {
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale('eng-GB');
        $this->assertEquals('global-en', $siteaccess);

        $market = $this->localeRoutingService->getCurrentMarket('global-en');
        $this->assertEquals('global', $market);

        $displayName = $this->localeRoutingService->getLocaleDisplayName('eng-GB');
        $this->assertEquals('English (Global)', $displayName);
    }

    public function testThailandEnglishSiteaccessMapping(): void
    {
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale('eng-TH');
        $this->assertEquals('th-en', $siteaccess);

        $market = $this->localeRoutingService->getCurrentMarket('th-en');
        $this->assertEquals('th', $market);

        $displayName = $this->localeRoutingService->getLocaleDisplayName('eng-TH');
        $this->assertEquals('English (Thailand)', $displayName);
    }

    public function testMalaysiaEnglishSiteaccessMapping(): void
    {
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale('eng-MY');
        $this->assertEquals('my-en', $siteaccess);

        $market = $this->localeRoutingService->getCurrentMarket('my-en');
        $this->assertEquals('my', $market);

        $displayName = $this->localeRoutingService->getLocaleDisplayName('eng-MY');
        $this->assertEquals('English (Malaysia)', $displayName);
    }

    public function testMalaysiaThaiSiteaccessMapping(): void
    {
        $siteaccess = $this->localeRoutingService->getSiteaccessForLocale('tha-TH');
        $this->assertEquals('th-th', $siteaccess);

        $market = $this->localeRoutingService->getCurrentMarket('th-th');
        $this->assertEquals('th', $market);

        $displayName = $this->localeRoutingService->getLocaleDisplayName('tha-TH');
        $this->assertEquals('ภาษาไทย (Thai)', $displayName);
    }

    public function testGlobalUrlPrefixGeneration(): void
    {
        $prefix = $this->localeRoutingService->getSiteaccessUrlPrefix('global-en');
        $this->assertEquals('/global-en', $prefix, 'Global should have /global-en URL prefix');

        $url = $this->localeRoutingService->buildFullUrl('global', 'eng-GB', '/news/article');
        $this->assertEquals('/global-en/news/article', $url);
    }

    public function testThailandUrlPrefixGeneration(): void
    {
        $prefix = $this->localeRoutingService->getSiteaccessUrlPrefix('th-en');
        $this->assertEquals('/th-en', $prefix);

        $url = $this->localeRoutingService->buildFullUrl('th', 'eng-TH', '/news/article');
        $this->assertEquals('/th-en/news/article', $url);
    }

    public function testMalaysiaEnglishUrlPrefixGeneration(): void
    {
        $prefix = $this->localeRoutingService->getSiteaccessUrlPrefix('my-en');
        $this->assertEquals('/my-en', $prefix);

        $url = $this->localeRoutingService->buildFullUrl('my', 'eng-MY', '/news/article');
        $this->assertEquals('/my-en/news/article', $url);
    }

    public function testMalaysiaThaiUrlPrefixGeneration(): void
    {
        $prefix = $this->localeRoutingService->getSiteaccessUrlPrefix('th-th');
        $this->assertEquals('/th-th', $prefix);

        $url = $this->localeRoutingService->buildFullUrl('my', 'tha-TH', '/news/article');
        $this->assertEquals('/th-th/news/article', $url);
    }

    public function testGlobalUrlParsing(): void
    {
        $result = $this->localeRoutingService->parseUrl('/news/article');

        $this->assertEquals('global', $result['market']);
        $this->assertEquals('eng-GB', $result['locale']);
        $this->assertEquals('global-en', $result['siteaccess']);
    }

    public function testThailandUrlParsing(): void
    {
        $result = $this->localeRoutingService->parseUrl('/th-en/news/article');

        $this->assertEquals('th', $result['market']);
        $this->assertEquals('eng-TH', $result['locale']);
        $this->assertEquals('th-en', $result['siteaccess']);
    }

    public function testMalaysiaEnglishUrlParsing(): void
    {
        $result = $this->localeRoutingService->parseUrl('/my-en/news/article');

        $this->assertEquals('my', $result['market']);
        $this->assertEquals('eng-MY', $result['locale']);
        $this->assertEquals('my-en', $result['siteaccess']);
    }

    public function testMalaysiaThaiUrlParsing(): void
    {
        $result = $this->localeRoutingService->parseUrl('/th-th/news/article');

        $this->assertEquals('th', $result['market']);
        $this->assertEquals('tha-TH', $result['locale']);
        $this->assertEquals('th-th', $result['siteaccess']);
    }

    public function testSiteaccessMatching(): void
    {
        $this->assertTrue($this->localeRoutingService->matchesSiteaccess('/news/article', 'global-en'));
        $this->assertFalse($this->localeRoutingService->matchesSiteaccess('/th-en/news/article', 'global-en'));

        $this->assertTrue($this->localeRoutingService->matchesSiteaccess('/th-en/news/article', 'th-en'));
        $this->assertFalse($this->localeRoutingService->matchesSiteaccess('/news/article', 'th-en'));

        $this->assertTrue($this->localeRoutingService->matchesSiteaccess('/my-en/news/article', 'my-en'));
        $this->assertFalse($this->localeRoutingService->matchesSiteaccess('/th-th/news/article', 'my-en'));

        $this->assertTrue($this->localeRoutingService->matchesSiteaccess('/th-th/news/article', 'th-th'));
        $this->assertFalse($this->localeRoutingService->matchesSiteaccess('/my-en/news/article', 'th-th'));
    }

    public function testLocalePrefixStripping(): void
    {
        $this->assertEquals('/news/article', $this->localeRoutingService->stripLocalePrefix('/global-en/news/article'));
        $this->assertEquals('/news/article', $this->localeRoutingService->stripLocalePrefix('/news/article'));
        $this->assertEquals('/news/article', $this->localeRoutingService->stripLocalePrefix('/th-en/news/article'));
        $this->assertEquals('/news/article', $this->localeRoutingService->stripLocalePrefix('/my-en/news/article'));
        $this->assertEquals('/news/article', $this->localeRoutingService->stripLocalePrefix('/th-th/news/article'));
    }

    public function testMarketUrlPrefixes(): void
    {
        $this->assertEquals('/global-en', $this->localeRoutingService->getMarketUrlPrefix('global'));
        $this->assertEquals('/th-en', $this->localeRoutingService->getMarketUrlPrefix('th'));
        $this->assertEquals('/my-en', $this->localeRoutingService->getMarketUrlPrefix('my'));
    }

    public function testLocaleUrlSuffixes(): void
    {
        // No locale suffixes in new structure - each siteaccess has unique prefix
        $this->assertEquals('', $this->localeRoutingService->getLocaleUrlSuffix('eng-GB'));
        $this->assertEquals('', $this->localeRoutingService->getLocaleUrlSuffix('eng-TH'));
        $this->assertEquals('', $this->localeRoutingService->getLocaleUrlSuffix('eng-MY'));
        $this->assertEquals('', $this->localeRoutingService->getLocaleUrlSuffix('tha-TH'));
    }

    public function testAllSiteaccessesHaveUniqueUrlPrefixes(): void
    {
        $prefixes = [
            'global-en' => $this->localeRoutingService->getSiteaccessUrlPrefix('global-en'),
            'th-en' => $this->localeRoutingService->getSiteaccessUrlPrefix('th-en'),
            'my-en' => $this->localeRoutingService->getSiteaccessUrlPrefix('my-en'),
            'th-th' => $this->localeRoutingService->getSiteaccessUrlPrefix('th-th'),
        ];

        // Filter out empty prefix (global)
        $nonEmptyPrefixes = array_filter($prefixes);

        $this->assertCount(
            count($nonEmptyPrefixes),
            array_unique($nonEmptyPrefixes),
            'All non-global siteaccesses should have unique URL prefixes'
        );
    }

    public function testComplexUrlBuildingForAllLocales(): void
    {
        // Test with virtual segments
        $testCases = [
            ['global', 'eng-GB', '/news/article-title', '/global-en/news/article-title'],
            ['th', 'eng-TH', '/news/article-title', '/th-en/news/article-title'],
            ['my', 'eng-MY', '/news/article-title', '/my-en/news/article-title'],
            ['th', 'tha-TH', '/news/article-title', '/th-th/news/article-title'],
        ];

        foreach ($testCases as [$market, $locale, $path, $expected]) {
            $url = $this->localeRoutingService->buildFullUrl($market, $locale, $path);
            $this->assertEquals($expected, $url, "Failed for {$market}/{$locale}");
        }
    }

    public function testUrlParsingForAllLocales(): void
    {
        $testCases = [
            ['/global-en/news/article', 'global', 'eng-GB', 'global-en'],
            ['/th-en/news/article', 'th', 'eng-TH', 'th-en'],
            ['/my-en/news/article', 'my', 'eng-MY', 'my-en'],
            ['/th-th/news/article', 'th', 'tha-TH', 'th-th'],
        ];

        foreach ($testCases as [$url, $expectedMarket, $expectedLocale, $expectedSiteaccess]) {
            $result = $this->localeRoutingService->parseUrl($url);

            $this->assertEquals($expectedMarket, $result['market'], "Market mismatch for {$url}");
            $this->assertEquals($expectedLocale, $result['locale'], "Locale mismatch for {$url}");
            $this->assertEquals($expectedSiteaccess, $result['siteaccess'], "Siteaccess mismatch for {$url}");
        }
    }

    public function testFallbackChainIntegrity(): void
    {
        // Thailand fallback: tha-TH → eng-TH → eng-GB
        $thLocales = $this->localeRoutingService->getMarketLocales('th');
        $this->assertEquals(['eng-TH', 'tha-TH', 'eng-GB'], $thLocales);

        // Malaysia English fallback: eng-MY → eng-GB
        $myEngLocales = $this->localeRoutingService->getMarketLocales('my');
        $this->assertContains('eng-MY', $myEngLocales);
        $this->assertContains('eng-GB', $myEngLocales);

        // Malaysia does not have Thai anymore
        $this->assertNotContains('tha-TH', $myEngLocales);
    }
}
