<?php

declare(strict_types=1);

namespace App\Service;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service to handle URL redirects based on Redirect content type
 */
class RedirectService
{
    public function __construct(
        private SearchService $searchService,
        private ContentService $contentService,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Find and execute redirect for the given source URL
     */
    public function findRedirect(string $sourceUrl): ?RedirectResponse
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier(['redirect']),
            new Criterion\Field('active', Criterion\Operator::EQ, true),
            new Criterion\Field('source_url', Criterion\Operator::EQ, $sourceUrl),
        ]);
        $query->limit = 1;

        $results = $this->searchService->findContent($query);

        if ($results->totalCount === 0) {
            return null;
        }

        $redirectContent = $results->searchHits[0]->valueObject;
        assert($redirectContent instanceof Content);

        return $this->createRedirectResponse($redirectContent);
    }

    /**
     * Get all active redirects
     */
    public function getAllRedirects(): array
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ContentTypeIdentifier(['redirect']),
            new Criterion\Field('active', Criterion\Operator::EQ, true),
        ]);
        $query->limit = 1000;

        $results = $this->searchService->findContent($query);

        $redirects = [];
        foreach ($results->searchHits as $hit) {
            $content = $hit->valueObject;
            assert($content instanceof Content);
            $redirects[] = [
                'source' => $content->getFieldValue('source_url')->text ?? '',
                'target' => $content->getFieldValue('target_url')->link ?? '',
                'type' => $content->getFieldValue('redirect_type')->selection[0] ?? 0,
            ];
        }

        return $redirects;
    }

    /**
     * Create redirect response from redirect content
     */
    private function createRedirectResponse(Content $content): RedirectResponse
    {
        $targetUrl = $content->getFieldValue('target_url')->link ?? '/';
        $redirectType = $content->getFieldValue('redirect_type')->selection[0] ?? 0;

        // 0 = 301 Permanent, 1 = 302 Temporary
        $statusCode = $redirectType === 0 ? 301 : 302;

        return new RedirectResponse($targetUrl, $statusCode);
    }
}
