<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendProductFilterSorterDisablingEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductFilterSorterDisablingEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridManagerLink;

    /** @var DatagridInterface */
    private $datagrid;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfig;

    /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $searchDataSource */
    private $gridDatasource;

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $gridQuery;

    /** @var FrontendProductFilterSorterDisablingEventListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);
        $this->configurationProvider = $this->createMock(AttributeConfigurationProvider::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->datagridManagerLink = $this->createMock(ServiceLink::class);
        $this->gridQuery = $this->createMock(SearchQueryInterface::class);

        $gridParameters = new ParameterBag([]);
        $this->datagridConfig = $this->createMock(DatagridConfiguration::class);
        $this->gridDatasource = $this->createMock(SearchDatasource::class);
        $acceptor = new Acceptor();
        $this->datagrid = new Datagrid('datagrid', $this->datagridConfig, $gridParameters);
        $this->datagrid->setDatasource($this->gridDatasource);
        $this->datagrid->setAcceptor($acceptor);

        $this->listener = new FrontendProductFilterSorterDisablingEventListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            $this->configurationProvider,
            $this->productRepository,
            $this->doctrineHelper,
            $this->datagridManagerLink
        );
    }

    public function testOnSearchResultBeforeFeatureDisabled()
    {
        $event = new SearchResultBefore($this->datagrid, $this->gridQuery);

        $this->listener->addFeature('feature');
        $featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->setFeatureChecker($featureChecker);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature', null)
            ->willReturn(false);

        $this->gridQuery->expects($this->never())
            ->method('addAggregate');

        $this->productRepository->expects($this->never())
            ->method('getFamilyAttributeCountsQuery');

        $this->listener->onSearchResultBefore($event);
    }

    public function testOnSearchResultBeforeSameQueries()
    {
        $this->datagrid->getParameters()->add([
            '_filter' => ['filterableAttributeName2' => 'filterableAttributeName2'],
            '_sort_by' => ['sortableAttributeName1' => 'sortableAttributeName1'],
        ]);

        $eventBefore = new SearchResultBefore($this->datagrid, $this->gridQuery);

        $datagridManager = $this->createMock(ManagerInterface::class);
        $this->datagridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($datagridManager);

        $datagridManager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $this->gridDatasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($this->gridQuery);

        $this->gridQuery->expects($this->once())
            ->method('addAggregate')
            ->with(
                'familyAttributesCount',
                'integer.attribute_family_id',
                'count'
            );

        $this->productRepository->expects($this->never())
            ->method('getFamilyAttributeCountsQuery');

        $this->listener->onSearchResultBefore($eventBefore);

        $eventAfter = new SearchResultAfter($this->datagrid, $this->gridQuery, []);

        $resultQuery = new Query();
        $aggregatedData = [
            'familyAttributesCount' => [
                43 => 'attributeFamily43',
                45 => 'attributeFamily45',
            ],
        ];
        $result = new Result($resultQuery, [], 0, $aggregatedData);
        $this->gridQuery->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        list(
            $attributeActive,
            $attributeFilterable,
            $attributeActiveFilterable,
            $attributeActiveFilterableSearchable1,
            $attributeActiveFilterableSearchable2,
            $attributeSortable,
            $attributeActiveSortable,
            $attributeActiveSortableSearchable1,
            $attributeActiveSortableSearchable2,
            $attributeSearchable,
            $attributeActiveFilterableSortable,
            $attributeActiveFilterableSortableSearchable
        ) = $this->createAttributes();

        /** @var AttributeFamilyRepository|\PHPUnit\Framework\MockObject\MockObject $attributeFamilyRepository */
        $attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository);

        $attributeFamilyRepository->expects($this->once())
            ->method('getFamilyIdsForAttributes')
            ->with([
                $attributeActiveFilterableSearchable1,
                $attributeActiveFilterableSearchable2,
                $attributeActiveSortableSearchable1,
                $attributeActiveSortableSearchable2,
                $attributeActiveFilterableSortableSearchable,
            ])
            ->willReturn([
                36 => [111, 112],
                39 => [43, 48],
            ]);

        $this->datagridConfig->expects($this->exactly(2))
            ->method('offsetUnsetByPath')
            ->withConsecutive(
                ['[filters][columns][filterableAttributeName1_LOCALIZATION_ID]'],
                ['[sorters][columns][sortableAttributeName2_ENUM_ID]']
            );

        $this->listener->onSearchResultAfter($eventAfter);
    }

    public function testOnSearchResultBeforeDifferentQueries()
    {
        $event = new SearchResultBefore($this->datagrid, $this->gridQuery);

        $datagridManager = $this->createMock(ManagerInterface::class);
        $this->datagridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($datagridManager);

        $datagridManager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
        $datasourceQuery = $this->createMock(SearchQueryInterface::class);

        $this->gridDatasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($datasourceQuery);

        $this->gridQuery->expects($this->never())
            ->method('addAggregate');

        /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
        $searchQuery = $this->createMock(SearchQueryInterface::class);

        $this->productRepository->expects($this->once())
            ->method('getFamilyAttributeCountsQuery')
            ->with(
                $datasourceQuery,
                'familyAttributesCount'
            )
            ->willReturn($searchQuery);

        $this->listener->onSearchResultBefore($event);
    }

    public function testOnSearchResultAfterFeatureDisabled()
    {
        $event = new SearchResultAfter($this->datagrid, $this->gridQuery, []);

        $this->listener->addFeature('feature');
        $featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->setFeatureChecker($featureChecker);
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature', null)
            ->willReturn(false);

        $this->attributeManager->expects($this->never())
            ->method('getAttributesByClass');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->configurationProvider->expects($this->never())
            ->method('isAttributeFilterable');

        $this->configurationProvider->expects($this->never())
            ->method('isAttributeSortable');

        $this->attributeTypeRegistry->expects($this->never())
            ->method('getAttributeType');

        $this->datagridConfig->expects($this->never())
            ->method('offsetUnsetByPath');

        $this->listener->onSearchResultAfter($event);
    }

    public function testOnSearchResultAfterEmptyQueryWithAggregate()
    {
        $eventBefore = new SearchResultBefore($this->datagrid, $this->gridQuery);

        $datagridManager = $this->createMock(ManagerInterface::class);
        $this->datagridManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($datagridManager);

        $datagridManager->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
        $datasourceQuery = $this->createMock(SearchQueryInterface::class);

        $this->gridDatasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($datasourceQuery);

        $this->gridQuery->expects($this->never())
            ->method('addAggregate');

        $this->productRepository->expects($this->once())
            ->method('getFamilyAttributeCountsQuery')
            ->with(
                $datasourceQuery,
                'familyAttributesCount'
            )
            ->willReturn(null);

        $this->listener->onSearchResultBefore($eventBefore);

        $eventAfter = new SearchResultAfter($this->datagrid, $this->gridQuery, []);

        $this->attributeManager->expects($this->never())
            ->method('getAttributesByClass');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->configurationProvider->expects($this->never())
            ->method('isAttributeFilterable');

        $this->configurationProvider->expects($this->never())
            ->method('isAttributeSortable');

        $this->attributeTypeRegistry->expects($this->never())
            ->method('getAttributeType');

        $this->datagridConfig->expects($this->never())
            ->method('offsetUnsetByPath');

        $this->listener->onSearchResultAfter($eventAfter);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createAttributes()
    {
        $attributeActive = $this->getEntity(FieldConfigModel::class, ['id' => 30]);
        $attributeFilterable = $this->getEntity(FieldConfigModel::class, ['id' => 31]);
        $attributeActiveFilterable = $this->getEntity(FieldConfigModel::class, ['id' => 32]);
        $attributeActiveFilterableSearchable1 = $this->getEntity(FieldConfigModel::class, [
            'id' => 331,
            'fieldName' => 'filterableAttributeName1_LOCALIZATION_ID'
        ]);
        $attributeActiveFilterableSearchable2 = $this->getEntity(FieldConfigModel::class, [
            'id' => 332,
            'fieldName' => 'filterableAttributeName2_ENUM_ID'
        ]);
        $attributeSortable = $this->getEntity(FieldConfigModel::class, ['id' => 34]);
        $attributeActiveSortable = $this->getEntity(FieldConfigModel::class, ['id' => 35]);
        $attributeActiveSortableSearchable1 = $this->getEntity(FieldConfigModel::class, [
            'id' => 361,
            'fieldName' => 'sortableAttributeName1_LOCALIZATION_ID'
        ]);
        $attributeActiveSortableSearchable2 = $this->getEntity(FieldConfigModel::class, [
            'id' => 362,
            'fieldName' => 'sortableAttributeName2_ENUM_ID'
        ]);
        $attributeSearchable = $this->getEntity(FieldConfigModel::class, ['id' => 37]);
        $attributeActiveFilterableSortable = $this->getEntity(FieldConfigModel::class, ['id' => 38]);
        $attributeActiveFilterableSortableSearchable = $this->getEntity(FieldConfigModel::class, ['id' => 39]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn([
                $attributeActive,
                $attributeFilterable,
                $attributeActiveFilterable,
                $attributeActiveFilterableSearchable1,
                $attributeActiveFilterableSearchable2,
                $attributeSortable,
                $attributeActiveSortable,
                $attributeActiveSortableSearchable1,
                $attributeActiveSortableSearchable2,
                $attributeSearchable,
                $attributeActiveFilterableSortable,
                $attributeActiveFilterableSortableSearchable,
            ]);

        $this->configurationProvider->expects($this->atLeastOnce())
            ->method('isAttributeActive')
            ->willReturnCallback(function ($attribute) use (
                $attributeActive,
                $attributeActiveFilterable,
                $attributeActiveFilterableSearchable1,
                $attributeActiveFilterableSearchable2,
                $attributeActiveSortable,
                $attributeActiveSortableSearchable1,
                $attributeActiveSortableSearchable2,
                $attributeActiveFilterableSortable,
                $attributeActiveFilterableSortableSearchable
            ) {
                return in_array($attribute, [
                    $attributeActive,
                    $attributeActiveFilterable,
                    $attributeActiveFilterableSearchable1,
                    $attributeActiveFilterableSearchable2,
                    $attributeActiveSortable,
                    $attributeActiveSortableSearchable1,
                    $attributeActiveSortableSearchable2,
                    $attributeActiveFilterableSortable,
                    $attributeActiveFilterableSortableSearchable
                ], true);
            });

        $this->configurationProvider->expects($this->atLeastOnce())
            ->method('isAttributeFilterable')
            ->willReturnCallback(function ($attribute) use (
                $attributeFilterable,
                $attributeActiveFilterable,
                $attributeActiveFilterableSearchable1,
                $attributeActiveFilterableSearchable2,
                $attributeActiveFilterableSortable,
                $attributeActiveFilterableSortableSearchable
            ) {
                return in_array($attribute, [
                    $attributeFilterable,
                    $attributeActiveFilterable,
                    $attributeActiveFilterableSearchable1,
                    $attributeActiveFilterableSearchable2,
                    $attributeActiveFilterableSortable,
                    $attributeActiveFilterableSortableSearchable
                ], true);
            });

        $this->configurationProvider->expects($this->atLeastOnce())
            ->method('isAttributeSortable')
            ->willReturnCallback(function ($attribute) use (
                $attributeSortable,
                $attributeActiveSortable,
                $attributeActiveSortableSearchable1,
                $attributeActiveSortableSearchable2,
                $attributeActiveFilterableSortable,
                $attributeActiveFilterableSortableSearchable
            ) {
                return in_array($attribute, [
                    $attributeSortable,
                    $attributeActiveSortable,
                    $attributeActiveSortableSearchable1,
                    $attributeActiveSortableSearchable2,
                    $attributeActiveFilterableSortable,
                    $attributeActiveFilterableSortableSearchable
                ], true);
            });

        $this->attributeTypeRegistry->expects($this->atLeastOnce())
            ->method('getAttributeType')
            ->willReturnCallback(function ($attribute) use (
                $attributeActiveFilterableSearchable1,
                $attributeActiveFilterableSearchable2,
                $attributeActiveSortableSearchable1,
                $attributeActiveSortableSearchable2,
                $attributeSearchable,
                $attributeActiveFilterableSortableSearchable
            ) {
                if (in_array($attribute, [
                    $attributeActiveFilterableSearchable1,
                    $attributeActiveFilterableSearchable2,
                    $attributeActiveSortableSearchable1,
                    $attributeActiveSortableSearchable2,
                    $attributeSearchable,
                    $attributeActiveFilterableSortableSearchable,
                ], true)) {
                    return new StringSearchableAttributeType(new StringAttributeType());
                }

                return null;
            });

        return [
            $attributeActive,
            $attributeFilterable,
            $attributeActiveFilterable,
            $attributeActiveFilterableSearchable1,
            $attributeActiveFilterableSearchable2,
            $attributeSortable,
            $attributeActiveSortable,
            $attributeActiveSortableSearchable1,
            $attributeActiveSortableSearchable2,
            $attributeSearchable,
            $attributeActiveFilterableSortable,
            $attributeActiveFilterableSortableSearchable,
        ];
    }
}
