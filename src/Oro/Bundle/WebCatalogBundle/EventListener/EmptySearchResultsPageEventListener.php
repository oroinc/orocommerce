<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\RedirectBundle\Factory\SubRequestFactory;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\WebCatalogBundle\Provider\EmptySearchResultPageContentVariantProvider;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use Oro\Component\Routing\UrlUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Forwards user to a content node specified in the "Empty Search Result Page" configuration option
 * if there are no search results and no search term found.
 */
class EmptySearchResultsPageEventListener implements ResetInterface, FeatureCheckerAwareInterface, LoggerAwareInterface
{
    use FeatureCheckerHolderTrait;
    use LoggerAwareTrait;

    private array $applicableRoutes = ['oro_product_frontend_product_search'];

    private int $totalRecordsCount = 0;

    public function __construct(
        private SearchTermProvider $searchTermProvider,
        private EmptySearchResultPageContentVariantProvider $emptySearchResultPageContentVariantProvider,
        private LocalizationHelper $localizationHelper,
        private SubRequestFactory $subRequestFactory
    ) {
        $this->logger = new NullLogger();
    }

    public function setApplicableRoutes(array $applicableRoutes): void
    {
        $this->applicableRoutes = $applicableRoutes;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $request = $event->getRequest();

        $resolvedContentVariant = $this->emptySearchResultPageContentVariantProvider->getResolvedContentVariant();
        if (!$resolvedContentVariant) {
            $this->logger->warning(
                'Failed to forward to the empty search results page from the search page "{search}":'
                . ' no resolved content node found.',
                [
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );
            return;
        }

        $redirectUrl = $this->localizationHelper->getLocalizedValue($resolvedContentVariant->getLocalizedUrls());
        if (!$redirectUrl) {
            return;
        }

        $response = $this->getResponse($request, $event->getKernel(), $redirectUrl);

        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        $this->totalRecordsCount = count($event->getRecords());
    }

    /**
     * @param Request $request
     * @param HttpKernelInterface $httpKernel
     * @param string $redirectUrl
     *
     * @return Response|null
     */
    private function getResponse(Request $request, HttpKernelInterface $httpKernel, string $redirectUrl): ?Response
    {
        $redirectUrl = UrlUtil::getAbsolutePath($redirectUrl, $request->getBaseUrl());

        $loggingContext = [
            'url' => $redirectUrl,
            'search' => $request->get('search', 'n/a'),
            'request' => $request,
        ];

        $this->logger->debug(
            'Forwarding to the empty search results page "{url}" from the search page "{search}".',
            $loggingContext
        );

        $subRequest = $this->subRequestFactory->createSubRequest(
            $request,
            $redirectUrl,
            [
                'frontend-product-search-grid' => [
                    // Ensures that a content node will not be pre-filtered with the search phrase specified previously.
                    SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                ],
            ] + $request->query->all()
        );
        $response = $httpKernel->handle($subRequest);

        if ($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            $this->logger->warning(
                'Failed to forward to the empty search results page "{url}" from the search page "{search}":'
                . ' response status code is {response_status_code}.',
                $loggingContext + ['response_status_code' => $response->getStatusCode()]
            );

            // Response is not successful.
            return null;
        }

        return $response;
    }

    private function isApplicable(KernelEvent $event): bool
    {
        if (!\in_array($event->getRequest()->get('_route'), $this->applicableRoutes, true)) {
            // Empty search results page must not be available on an unsupported route.
            return false;
        }

        if (!$event->getRequest()->query->has('search')) {
            // Empty search results page must not be available when there is no any search phrase.
            return false;
        }

        if ($this->totalRecordsCount > 0) {
            $this->totalRecordsCount = 0;
            // Empty search results page must not be available when there are search results.
            return false;
        }

        if (!$this->isFeaturesEnabled()) {
            // Empty search results page must be available when search terms feature is not enabled.
            return true;
        }

        $searchTerm = $this->searchTermProvider->getMostSuitableSearchTerm($event->getRequest()->query->get('search'));
        if ($searchTerm) {
            // Empty search results page must not be available when a search term is found.
            return false;
        }

        return true;
    }

    #[\Override]
    public function reset(): void
    {
        $this->totalRecordsCount = 0;
    }
}
