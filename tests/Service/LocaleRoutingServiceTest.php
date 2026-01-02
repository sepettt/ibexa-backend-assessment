<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\LocaleRoutingService;
use Ibexa\Bundle\Core\Routing\DefaultRouter;
use PHPUnit\Framework\TestCase;

class LocaleRoutingServiceTest extends TestCase
{
    private LocaleRoutingService $service;

    private DefaultRouter $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(DefaultRouter::class);
        $this->service = new LocaleRoutingService($this->router);
    }

    public function testGetCurrentMarketForGlobalEng(): void
    {
        $this->assertEquals('global', $this->service->getCurrentMarket('global-en'));
    }

    public function testGetCurrentMarketForThailand(): void
    {
        $this->assertEquals('th', $this->service->getCurrentMarket('th-en'));
    }

    public function testGetCurrentMarketForMalaysiaEnglish(): void
    {
        $this->assertEquals('my', $this->service->getCurrentMarket('my-en'));
    }

    public function testGetCurrentMarketForMalaysiaThai(): void
    {
        $this->assertEquals('th', $this->service->getCurrentMarket('th-th'));
    }

    public function testGetMarketLocalesForGlobal(): void
    {
        $locales = $this->service->getMarketLocales('global');
        $this->assertEquals(['eng-GB'], $locales);
    }

    public function testGetMarketLocalesForThailand(): void
    {
        $locales = $this->service->getMarketLocales('th');
        $this->assertContains('eng-TH', $locales);
        $this->assertContains('eng-GB', $locales);
    }

    public function testGetMarketLocalesForMalaysia(): void
    {
        $locales = $this->service->getMarketLocales('my');
        $this->assertContains('eng-MY', $locales);
        $this->assertContains('eng-GB', $locales);
    }

    public function testGetSiteaccessForLocaleGlobal(): void
    {
        $this->assertEquals('global-en', $this->service->getSiteaccessForLocale('eng-GB'));
    }

    public function testGetSiteaccessForLocaleThailand(): void
    {
        $this->assertEquals('th-en', $this->service->getSiteaccessForLocale('eng-TH'));
    }

    public function testGetSiteaccessForLocaleMalaysiaEnglish(): void
    {
        $this->assertEquals('my-en', $this->service->getSiteaccessForLocale('eng-MY'));
    }

    public function testGetSiteaccessForLocaleMalaysiaThai(): void
    {
        $this->assertEquals('th-th', $this->service->getSiteaccessForLocale('tha-TH'));
    }

    public function testGetSiteaccessUrlPrefixForGlobal(): void
    {
        $this->assertEquals('/global-en', $this->service->getSiteaccessUrlPrefix('global-en'));
    }

    public function testGetSiteaccessUrlPrefixForThailand(): void
    {
        $this->assertEquals('/th-en', $this->service->getSiteaccessUrlPrefix('th-en'));
    }

    public function testGetSiteaccessUrlPrefixForMalaysiaEnglish(): void
    {
        $this->assertEquals('/my-en', $this->service->getSiteaccessUrlPrefix('my-en'));
    }

    public function testGetSiteaccessUrlPrefixForMalaysiaThai(): void
    {
        $this->assertEquals('/th-th', $this->service->getSiteaccessUrlPrefix('th-th'));
    }

    public function testGetSiteaccessUrlPrefixForAdmin(): void
    {
        $this->assertEquals('/admin', $this->service->getSiteaccessUrlPrefix('admin'));
    }

    public function testGetMarketUrlPrefixForGlobal(): void
    {
        $this->assertEquals('/global-en', $this->service->getMarketUrlPrefix('global'));
    }

    public function testGetMarketUrlPrefixForThailand(): void
    {
        $this->assertEquals('/th-en', $this->service->getMarketUrlPrefix('th'));
    }

    public function testGetMarketUrlPrefixForMalaysia(): void
    {
        $this->assertEquals('/my-en', $this->service->getMarketUrlPrefix('my'));
    }

    public function testGetLocaleUrlSuffixForMalaysiaEnglish(): void
    {
        $this->assertEquals('', $this->service->getLocaleUrlSuffix('eng-MY'));
    }

    public function testGetLocaleUrlSuffixForMalaysiaThai(): void
    {
        $this->assertEquals('', $this->service->getLocaleUrlSuffix('tha-TH'));
    }

    public function testGetLocaleUrlSuffixForNonMalaysia(): void
    {
        $this->assertEquals('', $this->service->getLocaleUrlSuffix('eng-GB'));
        $this->assertEquals('', $this->service->getLocaleUrlSuffix('eng-TH'));
    }

    public function testBuildFullUrlForGlobal(): void
    {
        $url = $this->service->buildFullUrl('global', 'eng-GB', '/news/article-title');
        $this->assertEquals('/global-en/news/article-title', $url);
    }

    public function testBuildFullUrlForThailand(): void
    {
        $url = $this->service->buildFullUrl('th', 'eng-TH', '/news/article-title');
        $this->assertEquals('/th-en/news/article-title', $url);
    }

    public function testBuildFullUrlForMalaysiaEnglish(): void
    {
        $url = $this->service->buildFullUrl('my', 'eng-MY', '/news/article-title');
        $this->assertEquals('/my-en/news/article-title', $url);
    }

    public function testBuildFullUrlForMalaysiaThai(): void
    {
        $url = $this->service->buildFullUrl('th', 'tha-TH', '/news/article-title');
        $this->assertEquals('/th-th/news/article-title', $url);
    }

    public function testParseUrlForGlobal(): void
    {
        $result = $this->service->parseUrl('/news/article');
        $this->assertEquals('global', $result['market']);
        $this->assertEquals('eng-GB', $result['locale']);
        $this->assertEquals('global-en', $result['siteaccess']);
    }

    public function testParseUrlForThailand(): void
    {
        $result = $this->service->parseUrl('/th-en/news/article');
        $this->assertEquals('th', $result['market']);
        $this->assertEquals('eng-TH', $result['locale']);
        $this->assertEquals('th-en', $result['siteaccess']);
    }

    public function testParseUrlForMalaysiaEnglish(): void
    {
        $result = $this->service->parseUrl('/my-en/news/article');
        $this->assertEquals('my', $result['market']);
        $this->assertEquals('eng-MY', $result['locale']);
        $this->assertEquals('my-en', $result['siteaccess']);
    }

    public function testParseUrlForMalaysiaThai(): void
    {
        $result = $this->service->parseUrl('/th-th/news/article');
        $this->assertEquals('th', $result['market']);
        $this->assertEquals('tha-TH', $result['locale']);
        $this->assertEquals('th-th', $result['siteaccess']);
    }

    public function testParseUrlForAdmin(): void
    {
        $result = $this->service->parseUrl('/admin/dashboard');
        $this->assertEquals('global', $result['market']);
        $this->assertEquals('eng-GB', $result['locale']);
        $this->assertEquals('admin', $result['siteaccess']);
    }

    public function testMatchesSiteaccessForGlobal(): void
    {
        $this->assertTrue($this->service->matchesSiteaccess('/news/article', 'global-en'));
        $this->assertFalse($this->service->matchesSiteaccess('/th-en/news/article', 'global-en'));
    }

    public function testMatchesSiteaccessForThailand(): void
    {
        $this->assertTrue($this->service->matchesSiteaccess('/th-en/news/article', 'th-en'));
        $this->assertFalse($this->service->matchesSiteaccess('/news/article', 'th-en'));
    }

    public function testStripLocalePrefixFromGlobal(): void
    {
        $this->assertEquals('/news/article', $this->service->stripLocalePrefix('/news/article'));
    }

    public function testStripLocalePrefixFromThailand(): void
    {
        $this->assertEquals('/news/article', $this->service->stripLocalePrefix('/th-en/news/article'));
    }

    public function testStripLocalePrefixFromMalaysiaEnglish(): void
    {
        $this->assertEquals('/news/article', $this->service->stripLocalePrefix('/my-en/news/article'));
    }

    public function testStripLocalePrefixFromMalaysiaThai(): void
    {
        $this->assertEquals('/news/article', $this->service->stripLocalePrefix('/th-th/news/article'));
    }

    public function testStripLocalePrefixFromAdmin(): void
    {
        $this->assertEquals('/dashboard', $this->service->stripLocalePrefix('/admin/dashboard'));
    }

    public function testGetAllMarkets(): void
    {
        $markets = $this->service->getAllMarkets();
        $this->assertContains('global', $markets);
        $this->assertContains('th', $markets);
        $this->assertContains('my', $markets);
    }

    public function testGetAllLocales(): void
    {
        $locales = $this->service->getAllLocales();
        $this->assertContains('eng-GB', $locales);
        $this->assertContains('eng-TH', $locales);
        $this->assertContains('eng-MY', $locales);
        $this->assertContains('tha-TH', $locales);
    }

    public function testGetLocaleDisplayName(): void
    {
        $this->assertEquals('English (Global)', $this->service->getLocaleDisplayName('eng-GB'));
        $this->assertEquals('English (Thailand)', $this->service->getLocaleDisplayName('eng-TH'));
        $this->assertEquals('English (Malaysia)', $this->service->getLocaleDisplayName('eng-MY'));
        $this->assertEquals('ภาษาไทย (Thai)', $this->service->getLocaleDisplayName('tha-TH'));
    }
}
