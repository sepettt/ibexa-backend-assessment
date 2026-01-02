<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\LanguageSwitcherService;
use App\Service\LocaleRoutingService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use PHPUnit\Framework\TestCase;

class LanguageSwitcherServiceTest extends TestCase
{
    private LanguageSwitcherService $service;
    private ContentService $contentService;
    private LocationService $locationService;
    private LocaleRoutingService $localeRoutingService;
    private SiteAccessServiceInterface $siteAccessService;

    protected function setUp(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->localeRoutingService = $this->createMock(LocaleRoutingService::class);
        $this->siteAccessService = $this->createMock(SiteAccessServiceInterface::class);

        $this->service = new LanguageSwitcherService(
            $this->contentService,
            $this->locationService,
            $this->localeRoutingService,
            $this->siteAccessService
        );
    }

    public function testGetAvailableLanguagesReturnsAllLocales(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);
        $location = $this->createLocation();

        // Mock current siteaccess
        $siteAccess = $this->createMock(SiteAccess::class);
        $siteAccess->name = 'global-en';
        $this->siteAccessService->method('getCurrent')->willReturn($siteAccess);

        // Mock locale routing service
        $this->localeRoutingService->method('getAllLocales')
            ->willReturn(['eng-GB', 'eng-TH', 'eng-MY', 'tha-TH']);

        $this->localeRoutingService->method('getCurrentMarket')
            ->willReturnCallback(fn($sa) => match ($sa) {
                'global-en' => 'global',
                'th-en' => 'th',
                'my-en', 'th-th' => 'my',
                default => 'global',
            });

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->willReturnCallback(fn($locale) => match ($locale) {
                'eng-GB' => 'global-en',
                'eng-TH' => 'th-en',
                'eng-MY' => 'my-en',
                'tha-TH' => 'th-th',
                default => 'global-en',
            });

        $this->localeRoutingService->method('getLocaleDisplayName')
            ->willReturnCallback(fn($locale) => match ($locale) {
                'eng-GB' => 'English (Global)',
                'eng-TH' => 'English (Thailand)',
                'eng-MY' => 'English (Malaysia)',
                'tha-TH' => 'Thai Melayu',
                default => $locale,
            });

        $this->localeRoutingService->method('buildFullUrl')
            ->willReturn('/test-url');

        $languages = $this->service->getAvailableLanguages($content, $location);

        $this->assertCount(4, $languages);
        $this->assertArrayHasKey('eng-GB', $languages);
        $this->assertArrayHasKey('eng-TH', $languages);
        $this->assertArrayHasKey('eng-MY', $languages);
        $this->assertArrayHasKey('tha-TH', $languages);

        // Check eng-GB is available and current
        $this->assertTrue($languages['eng-GB']['available']);
        $this->assertTrue($languages['eng-GB']['current']);
        $this->assertEquals('global-en', $languages['eng-GB']['siteaccess']);
        $this->assertEquals('global', $languages['eng-GB']['market']);

        // Check eng-TH is available but not current
        $this->assertTrue($languages['eng-TH']['available']);
        $this->assertFalse($languages['eng-TH']['current']);

        // Check eng-MY and tha-TH are not available
        $this->assertFalse($languages['eng-MY']['available']);
        $this->assertFalse($languages['tha-TH']['available']);
    }

    public function testGetLanguagesByMarketGroupsLanguagesByMarket(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);
        $location = $this->createLocation();

        $siteAccess = $this->createMock(SiteAccess::class);
        $siteAccess->name = 'global-en';
        $this->siteAccessService->method('getCurrent')->willReturn($siteAccess);

        $this->localeRoutingService->method('getAllLocales')
            ->willReturn(['eng-GB', 'eng-TH', 'eng-MY', 'tha-TH']);

        $this->localeRoutingService->method('getCurrentMarket')
            ->willReturnCallback(fn($sa) => match ($sa) {
                'global-en' => 'global',
                'th-en' => 'th',
                'my-en', 'th-th' => 'my',
                default => 'global',
            });

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->willReturnCallback(fn($locale) => match ($locale) {
                'eng-GB' => 'global-en',
                'eng-TH' => 'th-en',
                'eng-MY' => 'my-en',
                'tha-TH' => 'th-th',
                default => 'global-en',
            });

        $this->localeRoutingService->method('getLocaleDisplayName')
            ->willReturn('Test Language');

        $this->localeRoutingService->method('buildFullUrl')
            ->willReturn('/test-url');

        $groupedLanguages = $this->service->getLanguagesByMarket($content, $location);

        $this->assertArrayHasKey('global', $groupedLanguages);
        $this->assertArrayHasKey('th', $groupedLanguages);
        $this->assertArrayHasKey('my', $groupedLanguages);

        $this->assertArrayHasKey('eng-GB', $groupedLanguages['global']);
        $this->assertArrayHasKey('eng-TH', $groupedLanguages['th']);
        $this->assertArrayHasKey('eng-MY', $groupedLanguages['my']);
        $this->assertArrayHasKey('tha-TH', $groupedLanguages['my']);
    }

    public function testHasTranslationReturnsTrueWhenTranslationExists(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);

        $this->assertTrue($this->service->hasTranslation($content, 'eng-GB'));
        $this->assertTrue($this->service->hasTranslation($content, 'eng-TH'));
    }

    public function testHasTranslationReturnsFalseWhenTranslationDoesNotExist(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB']);

        $this->assertFalse($this->service->hasTranslation($content, 'eng-TH'));
        $this->assertFalse($this->service->hasTranslation($content, 'eng-MY'));
        $this->assertFalse($this->service->hasTranslation($content, 'tha-TH'));
    }

    public function testGetTranslationReturnsContentWhenTranslationExists(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);

        $translatedContent = $this->createContentWithLanguages(['eng-TH']);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with($content->id, ['eng-TH'])
            ->willReturn($translatedContent);

        $result = $this->service->getTranslation($content, 'eng-TH');

        $this->assertSame($translatedContent, $result);
    }

    public function testGetTranslationReturnsFallbackWhenTranslationDoesNotExist(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB']);

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->with('eng-MY')
            ->willReturn('my-en');

        $this->localeRoutingService->method('getCurrentMarket')
            ->with('my-en')
            ->willReturn('my');

        $this->localeRoutingService->method('getMarketLocales')
            ->with('my')
            ->willReturn(['eng-MY', 'eng-GB']);

        $fallbackContent = $this->createContentWithLanguages(['eng-GB']);

        $this->contentService->expects($this->once())
            ->method('loadContent')
            ->with($content->id, ['eng-GB'])
            ->willReturn($fallbackContent);

        $result = $this->service->getTranslation($content, 'eng-MY');

        $this->assertSame($fallbackContent, $result);
    }

    public function testGetTranslationReturnsNullWhenNoTranslationAvailable(): void
    {
        $content = $this->createContentWithLanguages(['tha-TH']);

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->with('eng-GB')
            ->willReturn('global-en');

        $this->localeRoutingService->method('getCurrentMarket')
            ->with('global-en')
            ->willReturn('global');

        $this->localeRoutingService->method('getMarketLocales')
            ->with('global')
            ->willReturn(['eng-GB']);

        $result = $this->service->getTranslation($content, 'eng-GB');

        $this->assertNull($result);
    }

    public function testGetAvailableMarketsReturnsMarketsWithTranslations(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);

        $this->localeRoutingService->method('getAllMarkets')
            ->willReturn(['global', 'th', 'my']);

        $this->localeRoutingService->method('getMarketLocales')
            ->willReturnCallback(fn($market) => match ($market) {
                'global' => ['eng-GB'],
                'th' => ['eng-TH', 'eng-GB'],
                'my' => ['eng-MY', 'tha-TH', 'eng-GB'],
                default => [],
            });

        $markets = $this->service->getAvailableMarkets($content);

        $this->assertCount(3, $markets);
        $this->assertContains('global', $markets);
        $this->assertContains('th', $markets);
        $this->assertContains('my', $markets);
    }

    public function testGetAvailableMarketsReturnsOnlyMarketsWithTranslations(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB']);

        $this->localeRoutingService->method('getAllMarkets')
            ->willReturn(['global', 'th', 'my']);

        $this->localeRoutingService->method('getMarketLocales')
            ->willReturnCallback(fn($market) => match ($market) {
                'global' => ['eng-GB'],
                'th' => ['eng-TH'],
                'my' => ['eng-MY', 'tha-TH'],
                default => [],
            });

        $markets = $this->service->getAvailableMarkets($content);

        $this->assertCount(1, $markets);
        $this->assertContains('global', $markets);
        $this->assertNotContains('th', $markets);
        $this->assertNotContains('my', $markets);
    }

    public function testIsFallbackLanguageReturnsFalseWhenContentHasTranslation(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH'], 'eng-GB');

        $result = $this->service->isFallbackLanguage($content, 'eng-GB');

        $this->assertFalse($result);
    }

    public function testIsFallbackLanguageReturnsTrueWhenUsingFallback(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB'], 'eng-GB');

        $result = $this->service->isFallbackLanguage($content, 'eng-TH');

        $this->assertTrue($result);
    }

    public function testGetDisplayedLanguageReturnsRequestedLanguageWhenAvailable(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB', 'eng-TH']);

        $result = $this->service->getDisplayedLanguage($content, 'eng-TH');

        $this->assertEquals('eng-TH', $result);
    }

    public function testGetDisplayedLanguageReturnsFallbackLanguageWhenNotAvailable(): void
    {
        $content = $this->createContentWithLanguages(['eng-GB'], 'eng-GB');

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->with('eng-MY')
            ->willReturn('my-en');

        $this->localeRoutingService->method('getCurrentMarket')
            ->with('my-en')
            ->willReturn('my');

        $this->localeRoutingService->method('getMarketLocales')
            ->with('my')
            ->willReturn(['eng-MY', 'eng-GB']);

        $result = $this->service->getDisplayedLanguage($content, 'eng-MY');

        $this->assertEquals('eng-GB', $result);
    }

    public function testGetDisplayedLanguageReturnsInitialLanguageWhenNoFallbackAvailable(): void
    {
        $content = $this->createContentWithLanguages(['tha-TH'], 'tha-TH');

        $this->localeRoutingService->method('getSiteaccessForLocale')
            ->with('eng-GB')
            ->willReturn('global-en');

        $this->localeRoutingService->method('getCurrentMarket')
            ->with('global-en')
            ->willReturn('global');

        $this->localeRoutingService->method('getMarketLocales')
            ->with('global')
            ->willReturn(['eng-GB']);

        $result = $this->service->getDisplayedLanguage($content, 'eng-GB');

        $this->assertEquals('tha-TH', $result);
    }

    /**
     * Helper to create a mock Content object with specific languages
     *
     * @param array<string> $languageCodes
     */
    private function createContentWithLanguages(array $languageCodes, ?string $initialLanguage = null): Content
    {
        $versionInfo = $this->createMock(VersionInfo::class);
        $versionInfo->method('__get')
            ->willReturnCallback(fn($name) => match ($name) {
                'languageCodes' => $languageCodes,
                'initialLanguageCode' => $initialLanguage ?? $languageCodes[0],
                default => null,
            });
        $versionInfo->languageCodes = $languageCodes;
        $versionInfo->initialLanguageCode = $initialLanguage ?? $languageCodes[0];

        $contentInfo = $this->createMock(ContentInfo::class);
        $contentInfo->id = 123;
        $contentInfo->method('__get')
            ->willReturn(123);

        $content = $this->createMock(Content::class);
        $content->id = 123;
        $content->method('getVersionInfo')->willReturn($versionInfo);
        $content->method('__get')
            ->willReturnCallback(fn($name) => match ($name) {
                'id' => 123,
                'contentInfo' => $contentInfo,
                default => null,
            });
        $content->contentInfo = $contentInfo;

        return $content;
    }

    private function createLocation(): Location
    {
        $location = $this->createMock(Location::class);
        $location->id = 456;
        $location->pathString = '/test/path';
        $location->method('__get')
            ->willReturnCallback(fn($name) => match ($name) {
                'id' => 456,
                'pathString' => '/test/path',
                default => null,
            });

        return $location;
    }
}
