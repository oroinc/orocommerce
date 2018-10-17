<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Updates configuration of frontend products grid to remove filters or sorters
 * based on information about product families that are used in products
 */
class FrontendProductFilterSorterDisablingEventListener
{
    use FeatureCheckerHolderTrait;

    /** @var string */
    const PRODUCT_FAMILIES_COUNT_ALIAS = 'familyAttributesCount';

    /** @var AttributeManager */
    protected $attributeManager;

    /** @var AttributeTypeRegistry */
    protected $attributeTypeRegistry;

    /** @var AttributeConfigurationProvider */
    protected $configurationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SearchQueryInterface|null */
    protected $queryWithAggregate;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var ServiceLink */
    private $datagridManagerLink;

    /**
     * @param AttributeManager               $attributeManager
     * @param AttributeTypeRegistry          $attributeTypeRegistry
     * @param AttributeConfigurationProvider $configurationProvider
     * @param ProductRepository              $productRepository
     * @param DoctrineHelper                 $doctrineHelper
     * @param ServiceLink                    $datagridManagerLink
     */
    public function __construct(
        AttributeManager $attributeManager,
        AttributeTypeRegistry $attributeTypeRegistry,
        AttributeConfigurationProvider $configurationProvider,
        ProductRepository $productRepository,
        DoctrineHelper $doctrineHelper,
        ServiceLink $datagridManagerLink
    ) {
        $this->attributeManager = $attributeManager;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
        $this->configurationProvider = $configurationProvider;
        $this->productRepository = $productRepository;
        $this->doctrineHelper = $doctrineHelper;
        $this->datagridManagerLink = $datagridManagerLink;
    }

    /**
     * @param SearchResultBefore $event
     */
    public function onSearchResultBefore(SearchResultBefore $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $queryWithoutFilters = $this->getSearchQueryFromGridWithoutAppliedFilters($event->getDatagrid());
        /** we assume that the objects won't be the same, but will be equal in some cases */
        if ($queryWithoutFilters == $event->getQuery()
            && $queryWithoutFilters instanceof SearchQueryInterface
            && $event->getQuery() instanceof SearchQueryInterface
        ) {
            // calculate category counts
            $event->getQuery()->addAggregate(
                self::PRODUCT_FAMILIES_COUNT_ALIAS,
                'integer.attribute_family_id',
                Query::AGGREGATE_FUNCTION_COUNT
            );

            $this->queryWithAggregate = $event->getQuery();

            return;
        }

        $searchQuery = $this->productRepository->getFamilyAttributeCountsQuery(
            $queryWithoutFilters,
            self::PRODUCT_FAMILIES_COUNT_ALIAS
        );

        $this->queryWithAggregate = $searchQuery;
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onSearchResultAfter(SearchResultAfter $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        if (null === $this->queryWithAggregate) {
            return;
        }

        $aggregateData = $this->queryWithAggregate->getResult()->getAggregatedData();
        $this->queryWithAggregate = null;

        if (empty($aggregateData[self::PRODUCT_FAMILIES_COUNT_ALIAS])) {
            return;
        }

        $activeAttributeFamilyIds = array_keys($aggregateData[self::PRODUCT_FAMILIES_COUNT_ALIAS]);
        $disabledAttributes = $this->getDisabledSortAndFilterAttributes($activeAttributeFamilyIds);

        $datagrid = $event->getDatagrid();
        $this->disableFilters($datagrid, $disabledAttributes);
        $this->disableSorters($datagrid, $disabledAttributes);
    }

    /**
     * @param array $activeAttributeFamilyIds
     *
     * @return array
     */
    protected function getDisabledSortAndFilterAttributes(array $activeAttributeFamilyIds)
    {
        $attributes = $this->attributeManager->getAttributesByClass(Product::class);
        $filterableSortableAttributes = array_values(
            array_filter($attributes, [$this, 'isApplicableAttribute'])
        );

        /** @var AttributeFamilyRepository $attributeFamilyRepository */
        $attributeFamilyRepository = $this->doctrineHelper->getEntityRepository(AttributeFamily::class);
        $familyIdsForAttributes = $attributeFamilyRepository->getFamilyIdsForAttributes($filterableSortableAttributes);

        return array_filter(
            $filterableSortableAttributes,
            function (FieldConfigModel $attribute) use ($familyIdsForAttributes, $activeAttributeFamilyIds) {
                /**
                 * Skip attributes without product families
                 */
                if (empty($familyIdsForAttributes[$attribute->getId()])) {
                    return true;
                }

                $activeAttributeFamilyIds = array_intersect(
                    $activeAttributeFamilyIds,
                    $familyIdsForAttributes[$attribute->getId()]
                );

                /**
                 * Skip attributes that are not included to active attribute families
                 */
                return empty($activeAttributeFamilyIds);
            }
        );
    }

    /**
     * @param FieldConfigModel $attribute
     * @return bool
     */
    protected function isApplicableAttribute(FieldConfigModel $attribute)
    {
        if ($this->configurationProvider->isAttributeActive($attribute) &&
            $this->configurationProvider->isAttributeFilterable($attribute) ||
            $this->configurationProvider->isAttributeSortable($attribute)
        ) {
            $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
            if ($attributeType instanceof SearchableAttributeTypeInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DatagridInterface $datagrid
     * @param array             $disabledAttributes
     */
    private function disableFilters(DatagridInterface $datagrid, array $disabledAttributes)
    {
        $filters = $datagrid->getParameters()->get(AbstractFilterExtension::FILTER_ROOT_PARAM);

        foreach ($disabledAttributes as $attribute) {
            if (!$this->configurationProvider->isAttributeFilterable($attribute)) {
                continue;
            }

            /** @var SearchableAttributeTypeInterface $attributeType */
            $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
            $name = $attributeType->getFilterableFieldName($attribute);
            $alias = $this->clearName($name);

            // these filters already have data
            if ($filters && array_key_exists($alias, $filters)) {
                continue;
            }

            $filterPath = sprintf(DatagridConfiguration::FILTER_PATH, $name);
            $datagrid->getConfig()->offsetUnsetByPath($filterPath);
        }
    }

    /**
     * @param DatagridInterface $datagrid
     * @param array             $disabledAttributes
     */
    private function disableSorters(DatagridInterface $datagrid, array $disabledAttributes)
    {
        $sorters = $datagrid->getParameters()->get(AbstractSorterExtension::SORTERS_ROOT_PARAM);

        foreach ($disabledAttributes as $attribute) {
            if (!$this->configurationProvider->isAttributeSortable($attribute)) {
                continue;
            }

            /** @var SearchableAttributeTypeInterface $attributeType */
            $attributeType = $this->attributeTypeRegistry->getAttributeType($attribute);
            $name = $attributeType->getSortableFieldName($attribute);
            $alias = $this->clearName($name);

            // check that sorter not in use
            if ($sorters && array_key_exists($alias, $sorters)) {
                continue;
            }

            $sorterPath = sprintf(DatagridConfiguration::SORTER_PATH, $name);
            $datagrid->getConfig()->offsetUnsetByPath($sorterPath);
        }
    }

    /**
     * @param DatagridInterface $datagrid
     *
     * @return SearchQueryInterface
     */
    private function getSearchQueryFromGridWithoutAppliedFilters(DatagridInterface $datagrid)
    {
        $clearDatagrid = $this->getGrid($datagrid->getConfig());

        /** @var SearchDatasource $datasource */
        $datasource = $clearDatagrid->acceptDatasource()->getDatasource();

        return $datasource->getSearchQuery();
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return DatagridInterface
     */
    private function getGrid(DatagridConfiguration $config)
    {
        /** @var Manager $datagridManager */
        $datagridManager = $this->datagridManagerLink->getService();

        return $datagridManager->getDatagrid($config->getName());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function clearName($name)
    {
        $placeholders = ['_'.LocalizationIdPlaceholder::NAME => '', '_'.EnumIdPlaceholder::NAME => ''];

        return strtr($name, $placeholders);
    }
}
