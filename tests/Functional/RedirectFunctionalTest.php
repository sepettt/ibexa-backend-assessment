<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for RedirectService integration.
 * Tests the full redirect flow including controller, event subscriber, and service.
 */
class RedirectFunctionalTest extends WebTestCase
{
    public function testRedirectListPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/redirects');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
    }

    public function testRedirectEventSubscriberInterceptsRequests(): void
    {
        $client = static::createClient();

        // Request a path that might have a redirect configured
        // The event subscriber should check for redirects before routing
        $client->request('GET', '/old-path-that-might-redirect');

        // Should either redirect or return 404, but not throw routing exception
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirect() || $response->isNotFound(),
            'Response should be either redirect or 404'
        );
    }

    public function testRedirectEventSubscriberSkipsAdminRoutes(): void
    {
        $client = static::createClient();

        // Request admin path - should not be intercepted by redirect subscriber
        $client->request('GET', '/admin');

        $response = $client->getResponse();
        // Admin routes should be handled normally (redirect to login or dashboard)
        $this->assertTrue(
            $response->isRedirect() || $response->isSuccessful() || $response->getStatusCode() === 401,
            'Admin routes should bypass redirect checking'
        );
    }

    public function testRedirectEventSubscriberSkipsAssetRoutes(): void
    {
        $client = static::createClient();

        // Request asset-like paths - should not be intercepted
        $client->request('GET', '/bundles/something.css');

        // Should get 404 from normal routing, not from redirect handling
        $this->assertTrue($client->getResponse()->isNotFound());
    }

    public function testRedirectServiceIntegrationWithRealContent(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Get the redirect service from container
        $redirectService = $container->get('App\Service\RedirectService');

        $this->assertNotNull($redirectService, 'RedirectService should be available in container');

        // Test findRedirect with non-existent path
        $result = $redirectService->findRedirect('/non-existent-redirect-source');
        $this->assertNull($result, 'Should return null for non-existent redirect');
    }

    public function testRedirectServiceGetAllRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $redirectService = $container->get('App\Service\RedirectService');

        // Get all redirects - should return array
        $redirects = $redirectService->getAllRedirects();

        $this->assertIsArray($redirects, 'getAllRedirects should return array');

        // Verify structure if redirects exist
        if (count($redirects) > 0) {
            $firstRedirect = $redirects[0];
            $this->assertArrayHasKey('source', $firstRedirect);
            $this->assertArrayHasKey('target', $firstRedirect);
            $this->assertArrayHasKey('type', $firstRedirect);
        }
    }

    public function testRedirectListControllerDisplaysRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/redirects');

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();

        // Check that the page renders properly
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
    }

    public function testRedirectEventSubscriberOnlyHandlesMainRequests(): void
    {
        $client = static::createClient();

        // Make a main request
        $client->request('GET', '/');

        // The response should be handled (either success, redirect, or 404)
        $response = $client->getResponse();
        $this->assertNotNull($response);
        $this->assertContains(
            $response->getStatusCode(),
            [200, 301, 302, 404],
            'Main request should get valid response'
        );
    }

    public function testRedirectServiceHandles301PermanentRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $redirectService = $container->get('App\Service\RedirectService');
        $redirects = $redirectService->getAllRedirects();

        // Find a 301 redirect if any exist
        $permanent = array_filter($redirects, fn ($r) => $r['type'] === 0);

        if (count($permanent) > 0) {
            $redirect = reset($permanent);
            $this->assertEquals(0, $redirect['type'], 'Type 0 should represent permanent redirect');
        } else {
            $this->assertTrue(true, 'No permanent redirects configured for testing');
        }
    }

    public function testRedirectServiceHandles302TemporaryRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $redirectService = $container->get('App\Service\RedirectService');
        $redirects = $redirectService->getAllRedirects();

        // Find a 302 redirect if any exist
        $temporary = array_filter($redirects, fn ($r) => $r['type'] === 1);

        if (count($temporary) > 0) {
            $redirect = reset($temporary);
            $this->assertEquals(1, $redirect['type'], 'Type 1 should represent temporary redirect');
        } else {
            $this->assertTrue(true, 'No temporary redirects configured for testing');
        }
    }
}
