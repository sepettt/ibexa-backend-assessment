<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\RedirectService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Core\FieldType\Selection\Value as SelectionValue;
use Ibexa\Core\FieldType\TextLine\Value as TextLineValue;
use Ibexa\Core\FieldType\Url\Value as UrlValue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectServiceTest extends TestCase
{
    private RedirectService $service;

    private SearchService $searchService;

    private ContentService $contentService;

    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->searchService = $this->createMock(SearchService::class);
        $this->contentService = $this->createMock(ContentService::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->service = new RedirectService(
            $this->searchService,
            $this->contentService,
            $this->urlGenerator
        );
    }

    public function testFindRedirectReturnsNullWhenNoRedirectFound(): void
    {
        $searchResult = new SearchResult([
            'totalCount' => 0,
            'searchHits' => [],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->with($this->callback(function (Query $query) {
                return $query->limit === 1;
            }))
            ->willReturn($searchResult);

        $result = $this->service->findRedirect('/old-page');

        $this->assertNull($result);
    }

    public function testFindRedirectReturns301PermanentRedirect(): void
    {
        $content = $this->createRedirectContent('/new-page', 0); // 0 = permanent
        $searchResult = $this->createSearchResult($content);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $response = $this->service->findRedirect('/old-page');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/new-page', $response->getTargetUrl());
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testFindRedirectReturns302TemporaryRedirect(): void
    {
        $content = $this->createRedirectContent('/temp-page', 1); // 1 = temporary
        $searchResult = $this->createSearchResult($content);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $response = $this->service->findRedirect('/old-temp');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/temp-page', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testFindRedirectSearchesForCorrectSourceUrl(): void
    {
        $searchResult = new SearchResult([
            'totalCount' => 0,
            'searchHits' => [],
        ]);
        $sourceUrl = '/specific-old-url';

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->with($this->callback(function (Query $query) use ($sourceUrl) {
                // Verify the query structure
                $this->assertEquals(1, $query->limit);
                return true;
            }))
            ->willReturn($searchResult);

        $this->service->findRedirect($sourceUrl);
    }

    public function testFindRedirectHandlesExternalUrl(): void
    {
        $content = $this->createRedirectContent('https://external.com/page', 0);
        $searchResult = $this->createSearchResult($content);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $response = $this->service->findRedirect('/external-link');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://external.com/page', $response->getTargetUrl());
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testFindRedirectDefaultsToRootWhenTargetUrlMissing(): void
    {
        $content = $this->createRedirectContent(null, 0);
        $searchResult = $this->createSearchResult($content);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $response = $this->service->findRedirect('/broken-redirect');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getTargetUrl());
    }

    public function testFindRedirectDefaultsTo301WhenRedirectTypeInvalid(): void
    {
        $content = $this->createRedirectContent('/target', 999); // Invalid type
        $searchResult = $this->createSearchResult($content);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $response = $this->service->findRedirect('/source');

        $this->assertEquals(302, $response->getStatusCode()); // Defaults to non-zero = 302
    }

    public function testGetAllRedirectsReturnsEmptyArrayWhenNoRedirects(): void
    {
        $searchResult = new SearchResult([
            'totalCount' => 0,
            'searchHits' => [],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->with($this->callback(function (Query $query) {
                return $query->limit === 1000;
            }))
            ->willReturn($searchResult);

        $redirects = $this->service->getAllRedirects();

        $this->assertIsArray($redirects);
        $this->assertCount(0, $redirects);
    }

    public function testGetAllRedirectsReturnsAllActiveRedirects(): void
    {
        $content1 = $this->createRedirectContentWithSource('/old1', '/new1', 0);
        $content2 = $this->createRedirectContentWithSource('/old2', '/new2', 1);
        $content3 = $this->createRedirectContentWithSource('/old3', '/new3', 0);

        $searchResult = new SearchResult([
            'totalCount' => 3,
            'searchHits' => [
                new SearchHit([
                    'valueObject' => $content1,
                ]),
                new SearchHit([
                    'valueObject' => $content2,
                ]),
                new SearchHit([
                    'valueObject' => $content3,
                ]),
            ],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $redirects = $this->service->getAllRedirects();

        $this->assertCount(3, $redirects);

        $this->assertEquals('/old1', $redirects[0]['source']);
        $this->assertEquals('/new1', $redirects[0]['target']);
        $this->assertEquals(0, $redirects[0]['type']);

        $this->assertEquals('/old2', $redirects[1]['source']);
        $this->assertEquals('/new2', $redirects[1]['target']);
        $this->assertEquals(1, $redirects[1]['type']);

        $this->assertEquals('/old3', $redirects[2]['source']);
        $this->assertEquals('/new3', $redirects[2]['target']);
        $this->assertEquals(0, $redirects[2]['type']);
    }

    public function testGetAllRedirectsLimitsTo1000Results(): void
    {
        $searchResult = new SearchResult([
            'totalCount' => 0,
            'searchHits' => [],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->with($this->callback(function (Query $query) {
                $this->assertEquals(1000, $query->limit);
                return true;
            }))
            ->willReturn($searchResult);

        $this->service->getAllRedirects();
    }

    public function testGetAllRedirectsHandlesMissingFields(): void
    {
        $content = $this->createRedirectContentWithSource('', '', 0);
        $searchResult = new SearchResult([
            'totalCount' => 1,
            'searchHits' => [
                new SearchHit([
                    'valueObject' => $content,
                ])],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $redirects = $this->service->getAllRedirects();

        $this->assertCount(1, $redirects);
        $this->assertEquals('', $redirects[0]['source']);
        $this->assertEquals('', $redirects[0]['target']);
        $this->assertEquals(0, $redirects[0]['type']);
    }

    public function testGetAllRedirectsHandlesDefaultRedirectType(): void
    {
        $content = $this->createRedirectContentWithSource('/source', '/target', null);
        $searchResult = new SearchResult([
            'totalCount' => 1,
            'searchHits' => [
                new SearchHit([
                    'valueObject' => $content,
                ])],
        ]);

        $this->searchService
            ->expects($this->once())
            ->method('findContent')
            ->willReturn($searchResult);

        $redirects = $this->service->getAllRedirects();

        $this->assertEquals(0, $redirects[0]['type']); // Defaults to 0
    }

    private function createSearchResult(Content $content): SearchResult
    {
        return new SearchResult([
            'totalCount' => 1,
            'searchHits' => [
                new SearchHit([
                    'valueObject' => $content,
                ])],
        ]);
    }

    private function createRedirectContent(?string $targetUrl, ?int $redirectType): Content
    {
        $content = $this->createMock(Content::class);

        $urlValue = $targetUrl !== null ? new UrlValue($targetUrl) : new UrlValue();
        $selectionValue = $redirectType !== null ? new SelectionValue([$redirectType]) : new SelectionValue([]);

        $content->method('getFieldValue')
            ->willReturnCallback(function (string $fieldIdentifier) use ($urlValue, $selectionValue) {
                return match ($fieldIdentifier) {
                    'target_url' => $urlValue,
                    'redirect_type' => $selectionValue,
                    default => null,
                };
            });

        return $content;
    }

    private function createRedirectContentWithSource(?string $sourceUrl, ?string $targetUrl, ?int $redirectType): Content
    {
        $content = $this->createMock(Content::class);

        $sourceValue = $sourceUrl !== null ? new TextLineValue($sourceUrl) : new TextLineValue();
        $urlValue = $targetUrl !== null ? new UrlValue($targetUrl) : new UrlValue();
        $selectionValue = $redirectType !== null ? new SelectionValue([$redirectType]) : new SelectionValue([]);

        $content->method('getFieldValue')
            ->willReturnCallback(function (string $fieldIdentifier) use ($sourceValue, $urlValue, $selectionValue) {
                return match ($fieldIdentifier) {
                    'source_url' => $sourceValue,
                    'target_url' => $urlValue,
                    'redirect_type' => $selectionValue,
                    default => null,
                };
            });

        return $content;
    }
}
