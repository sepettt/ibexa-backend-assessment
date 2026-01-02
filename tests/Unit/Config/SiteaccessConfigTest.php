<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests to verify siteaccess configuration matches requirements
 */
class SiteaccessConfigTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $configFile = __DIR__ . '/../../../config/packages/ibexa.yaml';
        $this->config = Yaml::parseFile($configFile);
    }

    public function testConfigurationFileExists(): void
    {
        $this->assertIsArray($this->config);
        $this->assertArrayHasKey('ibexa', $this->config);
    }

    public function testDefaultSiteaccessIsGlobalEng(): void
    {
        $this->assertEquals('global-en', $this->config['ibexa']['siteaccess']['default_siteaccess']);
    }

    public function testAllFourLocalesAreConfigured(): void
    {
        $siteaccesses = $this->config['ibexa']['siteaccess']['list'];

        $this->assertContains('global-en', $siteaccesses);
        $this->assertContains('th-en', $siteaccesses);
        $this->assertContains('my-en', $siteaccesses);
        $this->assertContains('th-th', $siteaccesses);
    }

    public function testAdminSiteaccessIsConfigured(): void
    {
        $siteaccesses = $this->config['ibexa']['siteaccess']['list'];
        $this->assertContains('admin', $siteaccesses);
    }

    public function testSiteaccessGroupsAreConfigured(): void
    {
        $groups = $this->config['ibexa']['siteaccess']['groups'];

        $this->assertArrayHasKey('site_group', $groups);
        $this->assertArrayHasKey('global_group', $groups);
        $this->assertArrayHasKey('singapore_group', $groups);
        $this->assertArrayHasKey('malaysia_group', $groups);
        $this->assertArrayHasKey('admin_group', $groups);
    }

    public function testSiteGroupContainsAllPublicSites(): void
    {
        $siteGroup = $this->config['ibexa']['siteaccess']['groups']['site_group'];

        $this->assertCount(4, $siteGroup);
        $this->assertContains('global-en', $siteGroup);
        $this->assertContains('th-en', $siteGroup);
        $this->assertContains('my-en', $siteGroup);
        $this->assertContains('th-th', $siteGroup);
    }

    public function testMalaysiaGroupContainsBothLanguages(): void
    {
        $malaysiaGroup = $this->config['ibexa']['siteaccess']['groups']['malaysia_group'];

        $this->assertCount(2, $malaysiaGroup);
        $this->assertContains('my-en', $malaysiaGroup);
        $this->assertContains('th-th', $malaysiaGroup);
    }

    public function testGlobalEngLanguageConfiguration(): void
    {
        $languages = $this->config['ibexa']['system']['global-en']['languages'];

        $this->assertCount(1, $languages);
        $this->assertEquals('eng-GB', $languages[0]);
    }

    public function testThailandLanguageConfiguration(): void
    {
        $languages = $this->config['ibexa']['system']['th-en']['languages'];

        $this->assertContains('eng-TH', $languages);
        $this->assertContains('eng-GB', $languages); // Fallback
    }

    public function testMalaysiaEnglishLanguageConfiguration(): void
    {
        $languages = $this->config['ibexa']['system']['my-en']['languages'];

        $this->assertContains('eng-MY', $languages);
        $this->assertContains('eng-GB', $languages); // Fallback
    }

    public function testMalaysiaThaiLanguageConfiguration(): void
    {
        $languages = $this->config['ibexa']['system']['th-th']['languages'];

        $this->assertContains('tha-TH', $languages);
        $this->assertContains('eng-MY', $languages); // First fallback
        $this->assertContains('eng-GB', $languages); // Second fallback
    }

    public function testUriMatchingForThailand(): void
    {
        $match = $this->config['ibexa']['siteaccess']['match']['Compound\URI'];

        $this->assertArrayHasKey('th', $match);
        $this->assertEquals('th', $match['th']['match']);
        $this->assertEquals('th-en', $match['th']['siteaccess']);
    }

    public function testUriMatchingForMalaysiaEnglish(): void
    {
        $match = $this->config['ibexa']['siteaccess']['match']['Compound\URI'];

        $this->assertArrayHasKey('my/eng', $match);
        $this->assertEquals('my/eng', $match['my/eng']['match']);
        $this->assertEquals('my-en', $match['my/eng']['siteaccess']);
    }

    public function testUriMatchingForMalaysiaThai(): void
    {
        $match = $this->config['ibexa']['siteaccess']['match']['Compound\URI'];

        $this->assertArrayHasKey('my/bm', $match);
        $this->assertEquals('my/bm', $match['my/bm']['match']);
        $this->assertEquals('th-th', $match['my/bm']['siteaccess']);
    }

    public function testUriMatchingForAdmin(): void
    {
        $match = $this->config['ibexa']['siteaccess']['match']['Compound\URI'];

        $this->assertArrayHasKey('admin', $match);
        $this->assertEquals('admin', $match['admin']['match']);
        $this->assertEquals('admin', $match['admin']['siteaccess']);
    }

    public function testAdminSiteaccessHasAdminDesign(): void
    {
        $design = $this->config['ibexa']['system']['admin']['design'];
        $this->assertEquals('admin', $design);
    }

    public function testAllPublicSitesHaveStandardDesign(): void
    {
        $this->assertEquals('standard', $this->config['ibexa']['system']['global-en']['design']);
        $this->assertEquals('standard', $this->config['ibexa']['system']['th-en']['design']);
        $this->assertEquals('standard', $this->config['ibexa']['system']['my-en']['design']);
        $this->assertEquals('standard', $this->config['ibexa']['system']['th-th']['design']);
    }
}
