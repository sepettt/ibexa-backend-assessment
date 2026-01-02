<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LocaleRoutingService;
use Ibexa\Bundle\Core\Controller;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends Controller
{
    public function __construct(
        private ContentService $contentService,
        private LocationService $locationService,
        private SearchService $searchService,
        private LocaleRoutingService $localeRoutingService,
        private ConfigResolverInterface $configResolver
    ) {
    }

    #[Route('/news', name: 'app_news_list', methods: ['GET'])]
    public function listAction(Request $request): Response
    {
        $currentLanguages = $this->configResolver->getParameter('languages');

        try {
            $query = new Query();
            $query->filter = new Criterion\LogicalAnd([
                new Criterion\ContentTypeIdentifier(['news']),
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            ]);
            $query->sortClauses = [
                new Query\SortClause\Field('news', 'publication_date', Query::SORT_DESC),
            ];
            $query->limit = 20;

            $searchResults = $this->searchService->findContent($query);

            return $this->render('@standard/news/list.html.twig', [
                'articles' => $searchResults->searchHits,
                'totalCount' => $searchResults->totalCount,
                'currentLanguages' => $currentLanguages,
            ]);
        } catch (\Exception $e) {
            // If search fails (e.g., Solr not configured), show empty list
            return $this->render('@standard/news/list.html.twig', [
                'articles' => [],
                'totalCount' => 0,
                'currentLanguages' => $currentLanguages,
                'error' => 'Search service temporarily unavailable. Please create news content in the admin interface.',
            ]);
        }
    }

    #[Route('/news/{slug}', name: 'app_news_view', methods: ['GET'])]
    public function viewAction(Request $request, string $slug): Response
    {
        // This would normally look up content by URL alias
        // For now, return a placeholder

        return $this->render('@standard/news/view.html.twig', [
            'slug' => $slug,
        ]);
    }
}
