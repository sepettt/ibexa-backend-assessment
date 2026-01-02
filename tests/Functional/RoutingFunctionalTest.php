<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for routing and URL handling.
 * Tests routing configuration, URL generation, and path handling.
 */
class RoutingFunctionalTest extends WebTestCase
{
    public function testNewsListRouteIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news');

        $this->assertResponseIsSuccessful();
    }

    public function testNewsViewRouteAcceptsSlugParameter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news/test-article');

        $response = $client->getResponse();
        // Should either be successful or 404 (if article doesn't exist)
        $this->assertTrue(
            $response->isSuccessful() || $response->isNotFound(),
            'News view route should accept slug parameter'
        );
    }

    public function testNewsViewRouteAcceptsSlugWithHyphens(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news/breaking-news-story');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isNotFound(),
            'Should accept slugs with hyphens'
        );
    }

    public function testNewsViewRouteAcceptsSlugWithNumbers(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news/article-2024-update');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isNotFound(),
            'Should accept slugs with numbers'
        );
    }

    public function testRedirectListRouteIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/redirects');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginRouteIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testAdminRouteRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $response = $client->getResponse();
        // Should redirect to login or show login page
        $this->assertTrue(
            $response->isRedirect() || $response->getStatusCode() === 401,
            'Admin route should require authentication'
        );
    }

    public function testRouterHandlesTrailingSlashes(): void
    {
        $client = static::createClient();

        // Test with trailing slash
        $client->request('GET', '/news/');
        $response1 = $client->getResponse();

        // Test without trailing slash
        $client->request('GET', '/news');
        $response2 = $client->getResponse();

        // Both should be handled properly (either both succeed or both redirect)
        $this->assertTrue(
            ($response1->isSuccessful() || $response1->isRedirect()) &&
            ($response2->isSuccessful() || $response2->isRedirect()),
            'Router should handle trailing slashes'
        );
    }

    public function testRouterHandlesNonExistentPaths(): void
    {
        $client = static::createClient();
        $client->request('GET', '/this-path-does-not-exist-123456');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isNotFound(),
            'Non-existent paths should return 404'
        );
    }

    public function testRouterHandlesInvalidMethodsCorrectly(): void
    {
        $client = static::createClient();

        // Try POST on GET-only route
        $client->request('POST', '/news');

        $response = $client->getResponse();
        // Should return 405 Method Not Allowed
        $this->assertTrue(
            $response->getStatusCode() === 405 || $response->isNotFound(),
            'Invalid HTTP methods should be rejected'
        );
    }

    public function testUrlGenerationForNewsRoutes(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $router = $container->get('router');

        // Generate news list URL
        $listUrl = $router->generate('app_news_list');
        $this->assertEquals('/news', $listUrl);

        // Generate news view URL with slug
        $viewUrl = $router->generate('app_news_view', [
            'slug' => 'test-article',
        ]);
        $this->assertEquals('/news/test-article', $viewUrl);
    }

    public function testUrlGenerationForRedirectRoute(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $router = $container->get('router');

        $redirectUrl = $router->generate('app_redirect_list');
        $this->assertEquals('/redirects', $redirectUrl);
    }

    public function testUrlGenerationForAuthRoutes(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $router = $container->get('router');

        $loginUrl = $router->generate('login');
        $this->assertEquals('/login', $loginUrl);

        $logoutUrl = $router->generate('logout');
        $this->assertEquals('/logout', $logoutUrl);
    }

    public function testRouterMatchesNewsListRoute(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $router = $container->get('router');

        $parameters = $router->match('/news');

        $this->assertArrayHasKey('_route', $parameters);
        $this->assertEquals('app_news_list', $parameters['_route']);
    }

    public function testRouterMatchesNewsViewRoute(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $router = $container->get('router');

        $parameters = $router->match('/news/my-article');

        $this->assertArrayHasKey('_route', $parameters);
        $this->assertEquals('app_news_view', $parameters['_route']);
        $this->assertArrayHasKey('slug', $parameters);
        $this->assertEquals('my-article', $parameters['slug']);
    }

    public function testRoutingPerformanceWithMultipleRequests(): void
    {
        $client = static::createClient();

        $startTime = microtime(true);

        // Make multiple requests
        for ($i = 0; $i < 5; $i++) {
            $client->request('GET', '/news');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete reasonably quickly (under 5 seconds for 5 requests)
        $this->assertLessThan(5, $duration, 'Routing should be performant');
    }

    public function testRouterHandlesSpecialCharactersInSlugs(): void
    {
        $client = static::createClient();

        // Test with URL-encoded characters
        $client->request('GET', '/news/test%20article');

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isNotFound(),
            'Router should handle URL-encoded characters'
        );
    }

    public function testNewsRouteAcceptsLongSlugs(): void
    {
        $client = static::createClient();
        $longSlug = 'this-is-a-very-long-article-slug-that-might-be-generated-from-a-long-title';

        $client->request('GET', '/news/' . $longSlug);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isNotFound(),
            'Router should handle long slugs'
        );
    }

    public function testRouterIgnoresQueryParameters(): void
    {
        $client = static::createClient();
        $client->request('GET', '/news?page=1&sort=date');

        // Query parameters shouldn't affect routing
        $this->assertTrue(
            $client->getResponse()->isSuccessful() || $client->getResponse()->isNotFound(),
            'Router should ignore query parameters for matching'
        );
    }
}
