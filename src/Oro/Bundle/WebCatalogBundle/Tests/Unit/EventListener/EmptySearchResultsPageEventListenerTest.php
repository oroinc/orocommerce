<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\RedirectBundle\Factory\SubRequestFactory;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\EventListener\EmptySearchResultsPageEventListener;
use Oro\Bundle\WebCatalogBundle\Provider\EmptySearchResultPageContentVariantProvider;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EmptySearchResultsPageEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private SearchTermProvider|MockObject $searchTermProvider;

    private LocalizationHelper|MockObject $localizationHelper;

    private EmptySearchResultPageContentVariantProvider|MockObject $emptySearchResultPageContentVariantProvider;

    private SubRequestFactory|MockObject $subRequestFactory;

    private EmptySearchResultsPageEventListener $listener;

    private FeatureChecker|MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchTermProvider = $this->createMock(SearchTermProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->emptySearchResultPageContentVariantProvider =
            $this->createMock(EmptySearchResultPageContentVariantProvider::class);
        $this->subRequestFactory = $this->createMock(SubRequestFactory::class);

        $this->listener = new EmptySearchResultsPageEventListener(
            $this->searchTermProvider,
            $this->emptySearchResultPageContentVariantProvider,
            $this->localizationHelper,
            $this->subRequestFactory
        );

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->addFeature('oro_website_search_terms_management');
        $this->listener->setFeatureChecker($this->featureChecker);

        $this->setUpLoggerMock($this->listener);
    }

    public function testWhenNotApplicableRoute(): void
    {
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelResponse($event);
    }

    public function testWhenNoSearchPhrase(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelResponse($event);
    }

    public function testWhenDatagridIsNotEmpty(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([$this->createMock(ResultRecordInterface::class)]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);
    }

    public function testWhenSearchTermFound(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(new SearchTerm());

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);

        $this->listener->onKernelResponse($event);
    }

    public function testWhenNoResolvedContentVariant(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(null);

        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn(null);

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to forward to the empty search results page from the search page "{search}":'
                . ' no resolved content node found.',
                [
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);
    }

    public function testWhenNoLocalizedUrl(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(null);

        $resolvedContentVariant = new ResolvedContentVariant();
        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn(null);

        $this->subRequestFactory
            ->expects(self::never())
            ->method(self::anything());

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);
    }

    public function testWhenNoResponse(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $originalResponse = new Response();
        $event = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $originalResponse
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(null);

        $resolvedContentVariant = new ResolvedContentVariant();
        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $redirectUrl = '/sample/page';
        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn($redirectUrl);

        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with($request, $redirectUrl)
            ->willReturn($subRequest);

        $httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest)
            ->willReturn(new Response('Not Found', 404));

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Forwarding to the empty search results page "{url}" from the search page "{search}".',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to forward to the empty search results page "{url}" from the search page "{search}":'
                . ' response status code is {response_status_code}.',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                    'response_status_code' => 404,
                ]
            );

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);

        self::assertSame($originalResponse, $event->getResponse());
    }

    public function testWhenHasResponse(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $originalResponse = new Response();
        $event = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $originalResponse
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn(null);

        $resolvedContentVariant = new ResolvedContentVariant();
        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $redirectUrl = '/sample/page';
        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn($redirectUrl);

        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with(
                $request,
                $redirectUrl,
                [
                    'frontend-product-search-grid' => [
                        // Ensures that a content node will not be pre-filtered with the search phrase
                        // specified previously.
                        SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                    ],
                ] + $request->query->all()
            )
            ->willReturn($subRequest);

        $newResponse = new Response('Empty search result page content');
        $httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest)
            ->willReturn($newResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Forwarding to the empty search results page "{url}" from the search page "{search}".',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);

        self::assertEquals($newResponse->getContent(), $event->getResponse()->getContent());
    }

    public function testWhenSearchTermsFeatureIsDisabledAndHasResponse(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $originalResponse = new Response();
        $event = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $originalResponse
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $this->searchTermProvider
            ->expects(self::never())
            ->method('getMostSuitableSearchTerm');

        $resolvedContentVariant = new ResolvedContentVariant();
        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $redirectUrl = '/sample/page';
        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn($redirectUrl);

        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with(
                $request,
                $redirectUrl,
                [
                    'frontend-product-search-grid' => [
                        // Ensures that a content node will not be pre-filtered with the search phrase
                        // specified previously.
                        SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                    ],
                ] + $request->query->all()
            )
            ->willReturn($subRequest);

        $newResponse = new Response('Empty search result page content');
        $httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest)
            ->willReturn($newResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Forwarding to the empty search results page "{url}" from the search page "{search}".',
                [
                    'url' => $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);

        self::assertEquals($newResponse->getContent(), $event->getResponse()->getContent());
    }

    public function testWhenHasBaseUrlAndHasResponse(): void
    {
        $request = new Request();
        ReflectionUtil::setPropertyValue($request, 'baseUrl', '/index_dev.php');
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample phrase';
        $request->query->set('search', $searchPhrase);
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $originalResponse = new Response();
        $event = new ResponseEvent(
            $httpKernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $originalResponse
        );

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $this->searchTermProvider
            ->expects(self::never())
            ->method('getMostSuitableSearchTerm');

        $resolvedContentVariant = new ResolvedContentVariant();
        $this->emptySearchResultPageContentVariantProvider
            ->expects(self::once())
            ->method('getResolvedContentVariant')
            ->willReturn($resolvedContentVariant);

        $redirectUrl = '/sample/page';
        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')
            ->with($resolvedContentVariant->getLocalizedUrls())
            ->willReturn($redirectUrl);

        $subRequest = $request->duplicate();
        $this->subRequestFactory
            ->expects(self::once())
            ->method('createSubRequest')
            ->with(
                $request,
                $request->getBaseUrl() . $redirectUrl,
                [
                    'frontend-product-search-grid' => [
                        // Ensures that a content node will not be pre-filtered with the search phrase
                        // specified previously.
                        SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY => '1',
                    ],
                ] + $request->query->all()
            )
            ->willReturn($subRequest);

        $newResponse = new Response('Empty search result page content');
        $httpKernel
            ->expects(self::once())
            ->method('handle')
            ->with($subRequest)
            ->willReturn($newResponse);

        $this->loggerMock
            ->expects(self::once())
            ->method('debug')
            ->with(
                'Forwarding to the empty search results page "{url}" from the search page "{search}".',
                [
                    'url' => $request->getBaseUrl() . $redirectUrl,
                    'search' => $request->get('search', 'n/a'),
                    'request' => $request,
                ]
            );

        $onResultAfterEvent = $this->createMock(SearchResultAfter::class);
        $onResultAfterEvent
            ->expects(self::once())
            ->method('getRecords')
            ->willReturn([]);

        $this->listener->onResultAfter($onResultAfterEvent);
        $this->listener->onKernelResponse($event);

        self::assertEquals($newResponse->getContent(), $event->getResponse()->getContent());
    }
}
