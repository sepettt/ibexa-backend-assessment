<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\RedirectService;
use Ibexa\Bundle\Core\Controller as BaseController;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for handling URL redirects based on Redirect content type.
 *
 * Searches for active redirects matching the current URL and performs
 * 301/302 redirects as configured in the content.
 */
class RedirectController extends BaseController
{
    private RedirectService $redirectService;

    private SearchService $searchService;

    private ContentService $contentService;

    public function __construct(
        RedirectService $redirectService,
        SearchService $searchService,
        ContentService $contentService
    ) {
        $this->redirectService = $redirectService;
        $this->searchService = $searchService;
        $this->contentService = $contentService;
    }

    /**
     * Handle redirect lookup and execution.
     *
     * This method:
     * 1. Extracts the requested path from the request
     * 2. Searches for active Redirect content matching the source URL
     * 3. Performs a 301 or 302 redirect to the target URL
     * 4. Returns 404 if no matching redirect is found
     *
     * @param Request $request The current HTTP request
     *
     * @return Response Either a RedirectResponse or 404
     *
     * @throws NotFoundHttpException When no matching redirect exists
     */
    public function handleRedirect(Request $request): Response
    {
        $requestedPath = $request->getPathInfo();

        // Search for active redirect with matching source URL
        $redirect = $this->findRedirectBySourceUrl($requestedPath);

        if ($redirect === null) {
            throw new NotFoundHttpException(sprintf(
                'No active redirect found for path: %s',
                $requestedPath
            ));
        }

        // Extract field values
        $content = $this->contentService->loadContent($redirect->id);
        $targetUrl = $content->getFieldValue('target_url')->text ?? '';
        $redirectType = (int) ($content->getFieldValue('redirect_type')->value ?? 301);

        // Validate target URL
        if (empty($targetUrl)) {
            throw new NotFoundHttpException(sprintf(
                'Redirect %d has empty target URL',
                $redirect->id
            ));
        }

        // Perform the redirect
        return new RedirectResponse(
            $targetUrl,
            $redirectType === 302 ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY
        );
    }

    /**
     * List all active redirects (admin view).
     *
     * Provides an overview of all configured redirects for administrators.
     * Shows source URL, target URL, redirect type, and status.
     *
     * @return Response HTML response with redirect listing
     */
    public function listRedirects(): Response
    {
        $query = new Query([
            'filter' => new Criterion\ContentTypeIdentifier('redirect'),
            'sortClauses' => [
                new Query\SortClause\DatePublished(Query::SORT_DESC),
            ],
            'limit' => 100,
        ]);

        $searchResult = $this->searchService->findContent($query);

        $redirects = [];
        foreach ($searchResult->searchHits as $hit) {
            $content = $hit->valueObject;
            assert($content instanceof Content);
            $redirects[] = [
                'id' => $content->id,
                'source_url' => $content->getFieldValue('source_url')->text ?? '',
                'target_url' => $content->getFieldValue('target_url')->text ?? '',
                'redirect_type' => (int) ($content->getFieldValue('redirect_type')->value ?? 301),
                'active' => (bool) ($content->getFieldValue('active')->bool ?? false),
                'published' => $content->contentInfo->publishedDate,
            ];
        }

        return $this->render('@standard/redirect/list.html.twig', [
            'redirects' => $redirects,
            'total' => $searchResult->totalCount,
        ]);
    }

    /**
     * Search for active Redirect content by source URL.
     *
     * @param string $sourceUrl The source URL path to match
     *
     * @return Content|null The matching content or null
     */
    private function findRedirectBySourceUrl(string $sourceUrl): ?Content
    {
        $query = new Query([
            'filter' => new Criterion\LogicalAnd([
                // Match content type
                new Criterion\ContentTypeIdentifier('redirect'),
                // Match source URL field
                new Criterion\Field('source_url', Criterion\Operator::EQ, $sourceUrl),
                // Only active redirects
                new Criterion\Field('active', Criterion\Operator::EQ, true),
            ]),
            'limit' => 1,
            'sortClauses' => [
                new Query\SortClause\DatePublished(Query::SORT_DESC),
            ],
        ]);

        $searchResult = $this->searchService->findContent($query);

        if ($searchResult->totalCount === 0) {
            return null;
        }

        $valueObject = $searchResult->searchHits[0]->valueObject;
        assert($valueObject instanceof Content);

        return $valueObject;
    }
}
