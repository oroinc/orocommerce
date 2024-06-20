<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ProductCollection;

// phpcs:disable Generic.Files.LineLength.TooLong
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection\ProductCollectionSearchTermFilteringEventListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\SegmentStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductCollectionSearchTermFilteringEventListenerTest extends WebTestCase
{
    private RequestStack $requestStack;

    private SearchTermProvider|MockObject $searchTermProvider;

    private FeatureChecker|MockObject $featureChecker;

    private ProductCollectionSearchTermFilteringEventListener $listener;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->searchTermProvider = $this->createMock(SearchTermProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductCollectionSearchTermFilteringEventListener(
            $this->requestStack,
            $this->searchTermProvider
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_website_search_terms_management');
    }

    public function testOnPreBuildWhenNoCurrentRequest(): void
    {
        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::never())
            ->method(self::anything());

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenRouteNotApplicable(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'sample_route');
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::never())
            ->method(self::anything());

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenFeatureNotEnabled(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenNoSearchQueryNoSearchTermParam(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenHasSearchQueryAndNoSearchTermFound(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample_phrase';
        $request->query->set('search', $searchPhrase);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

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

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenHasSearchQueryAndHasSearchTermNotModifyActionType(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample_phrase';
        $request->query->set('search', $searchPhrase);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $searchTerm = new SearchTermStub();
        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn($searchTerm);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenHasSearchQueryAndHasSearchTermNoProductCollectionSegment(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample_phrase';
        $request->query->set('search', $searchPhrase);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $searchTerm = (new SearchTermStub())
            ->setActionType('modify');
        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn($searchTerm);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenHasSearchQueryAndHasSearchTermWithProductCollectionSegment(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $searchPhrase = 'sample_phrase';
        $request->query->set('search', $searchPhrase);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);
        $parameterBag = new ParameterBag([]);
        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $segment = new SegmentStub(142);
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('modify')
            ->setProductCollectionSegment($segment);
        $this->searchTermProvider
            ->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($searchPhrase)
            ->willReturn($searchTerm);

        $datagridConfiguration = DatagridConfiguration::create([]);
        $event
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $this->listener->onPreBuild($event);

        self::assertEquals('1', $parameterBag->get(SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY));

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'productCollectionSearchTermId' => $searchTerm->getId(),
                        'productCollectionSearchTermSegmentId' => $segment->getId(),
                    ],
                ],
            ],
            $datagridConfiguration->toArray()
        );
    }

    public function testOnPreBuildWhenHasInvalidSearchTermParams(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);
        $parameterBag = new ParameterBag([
            'productCollectionSearchTermId' => 0,
            'productCollectionSearchTermSegmentId' => 0,
        ]);

        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::never())
            ->method('getMostSuitableSearchTerm');

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenHasSearchTermParams(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'oro_product_frontend_product_search');
        $this->requestStack->push($request);

        $segment = new SegmentStub(142);
        $searchTerm = (new SearchTermStub(42))
            ->setActionType('modify')
            ->setProductCollectionSegment($segment);

        $event = $this->createMock(PreBuild::class);
        $parameterBag = new ParameterBag([
            'productCollectionSearchTermId' => $searchTerm->getId(),
            'productCollectionSearchTermSegmentId' => $segment->getId(),
        ]);
        $event
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->searchTermProvider
            ->expects(self::never())
            ->method('getMostSuitableSearchTerm');

        $datagridConfiguration = DatagridConfiguration::create([]);
        $event
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $this->listener->onPreBuild($event);

        self::assertEquals($searchTerm->getId(), $parameterBag->get('productCollectionSearchTermId'));
        self::assertEquals($segment->getId(), $parameterBag->get('productCollectionSearchTermSegmentId'));
        self::assertEquals('1', $parameterBag->get(SearchEventListener::SKIP_FILTER_SEARCH_QUERY_KEY));

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'productCollectionSearchTermId' => $searchTerm->getId(),
                        'productCollectionSearchTermSegmentId' => $segment->getId(),
                    ],
                ],
            ],
            $datagridConfiguration->toArray()
        );
    }

    public function testOnBuildAfterWhenNoSearchDatasource(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datagrid
            ->expects(self::once())
            ->method('getDatasource')
            ->willReturn($this->createMock(DatasourceInterface::class));

        $datagrid
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWhenNoSearchTermIdNoSegmentId(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datasource = $this->createMock(SearchDatasource::class);
        $datagrid
            ->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $datagridConfiguration = DatagridConfiguration::create([]);
        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $datasource
            ->expects(self::never())
            ->method('getSearchQuery');

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWhenNoSegmentId(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datasource = $this->createMock(SearchDatasource::class);
        $datagrid
            ->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $searchTerm = new SearchTermStub(42);
        $datagridConfiguration = DatagridConfiguration::create([
            'options' => [
                'urlParams' => [
                    'productCollectionSearchTermId' => $searchTerm->getId(),
                ],
            ],
        ]);

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $datasource
            ->expects(self::never())
            ->method('getSearchQuery');

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWhenNoSearchTermId(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datasource = $this->createMock(SearchDatasource::class);
        $datagrid
            ->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $segment = new SegmentStub(142);
        $datagridConfiguration = DatagridConfiguration::create([
            'options' => [
                'urlParams' => [
                    'productCollectionSearchTermSegmentId' => $segment->getId(),
                ],
            ],
        ]);

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $datasource
            ->expects(self::never())
            ->method('getSearchQuery');

        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfter(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $event = new BuildAfter($datagrid);

        $datasource = $this->createMock(SearchDatasource::class);
        $datagrid
            ->expects(self::once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $segment = new SegmentStub(142);
        $searchTerm = new SearchTermStub(42);
        $datagridConfiguration = DatagridConfiguration::create([
            'options' => [
                'urlParams' => [
                    'productCollectionSearchTermId' => $searchTerm->getId(),
                    'productCollectionSearchTermSegmentId' => $segment->getId(),
                ],
            ],
        ]);

        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfiguration);

        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $datasource
            ->expects(self::once())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);

        $searchQuery
            ->expects(self::once())
            ->method('addWhere')
            ->with(
                Criteria::expr()->eq(
                    sprintf('integer.assigned_to.search_term_%s', $searchTerm->getId()),
                    $segment->getId()
                )
            );

        $this->listener->onBuildAfter($event);
    }
}
