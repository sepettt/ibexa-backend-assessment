<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber that intercepts requests to check for active redirects.
 *
 * This subscriber runs early in the request lifecycle to check if the requested
 * URL has a configured redirect. If found, it short-circuits the request and
 * returns the redirect response immediately.
 *
 * Priority is set to run before routing (priority 33) to avoid unnecessary
 * route matching for URLs that should redirect.
 */
class RedirectEventSubscriber implements EventSubscriberInterface
{
    private SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run before routing (router runs at priority 32)
            KernelEvents::REQUEST => ['onKernelRequest', 33],
        ];
    }

    /**
     * Check for active redirects and perform redirect if found.
     *
     * @param RequestEvent $event The request event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only handle master requests
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestedPath = $request->getPathInfo();

        // Skip admin routes
        if (str_starts_with($requestedPath, '/admin')) {
            return;
        }

        // Skip asset routes
        if (str_starts_with($requestedPath, '/bundles') ||
            str_starts_with($requestedPath, '/assets') ||
            str_contains($requestedPath, '.')) {
            return;
        }

        // Search for active redirect
        $redirect = $this->findActiveRedirect($requestedPath);

        if ($redirect === null) {
            return;
        }

        // Extract redirect details
        $targetUrl = $redirect['target_url'] ?? '';
        $redirectType = $redirect['redirect_type'] ?? 301;

        if (empty($targetUrl)) {
            return;
        }

        // Create and set redirect response
        $response = new RedirectResponse(
            $targetUrl,
            $redirectType === 302 ? Response::HTTP_FOUND : Response::HTTP_MOVED_PERMANENTLY
        );

        $event->setResponse($response);
    }

    /**
     * Find an active redirect for the given source URL.
     *
     * @param string $sourceUrl The source URL to match
     *
     * @return array|null Redirect data or null if not found
     */
    private function findActiveRedirect(string $sourceUrl): ?array
    {
        try {
            $query = new Query([
                'filter' => new Criterion\LogicalAnd([
                    new Criterion\ContentTypeIdentifier('redirect'),
                    new Criterion\Field('source_url', Criterion\Operator::EQ, $sourceUrl),
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

            $content = $searchResult->searchHits[0]->valueObject;
            assert($content instanceof \Ibexa\Contracts\Core\Repository\Values\Content\Content);

            return [
                'id' => $content->id,
                'target_url' => $content->getFieldValue('target_url')->text ?? '',
                'redirect_type' => (int) ($content->getFieldValue('redirect_type')->value ?? 301),
            ];
        } catch (\Exception $e) {
            // Log error but don't break the request
            // In production, use proper logger
            return null;
        }
    }
}
