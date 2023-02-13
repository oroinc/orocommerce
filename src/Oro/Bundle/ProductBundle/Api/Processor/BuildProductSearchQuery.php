<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\EngineInterface as SearchEngine;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria as SearchCriteria;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds a search query object that will be used to get product search data.
 */
class BuildProductSearchQuery implements ProcessorInterface
{
    private SearchEngine $searchEngine;
    private AbstractSearchMappingProvider $searchMappingProvider;
    private ProductManager $productManager;
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(
        SearchEngine $searchEngine,
        AbstractSearchMappingProvider $searchMappingProvider,
        ProductManager $productManager,
        FilterNamesRegistry $filterNamesRegistry
    ) {
        $this->searchEngine = $searchEngine;
        $this->searchMappingProvider = $searchMappingProvider;
        $this->productManager = $productManager;
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            return;
        }

        try {
            $context->setQuery(
                $this->buildSearchQuery($criteria, $this->getSelectFieldNames($context->getConfig()))
            );
            $context->setCriteria(null);
        } catch (InvalidSorterException $e) {
            $context->addError(
                Error::createValidationError(Constraint::SORT, $e->getMessage())
                    ->setSource(ErrorSource::createByParameter(
                        $this->getSortFilterName($context->getRequestType(), $context->getFilterValues())
                    ))
            );
        }
    }

    /**
     * @param Criteria $criteria
     * @param string[] $selectFieldNames
     *
     * @return WebsiteSearchQuery
     */
    private function buildSearchQuery(Criteria $criteria, array $selectFieldNames): WebsiteSearchQuery
    {
        $searchQuery = new SearchQuery();
        $searchQuery->from($this->searchMappingProvider->getEntityAlias(Product::class));
        $searchQuery->addSelect($selectFieldNames);

        $searchCriteria = new SearchCriteria(
            $criteria->getWhereExpression(),
            $this->resolveOrderings($criteria->getOrderings()),
            $criteria->getFirstResult(),
            $criteria->getMaxResults()
        );
        $searchQuery->setCriteria($searchCriteria);

        return new WebsiteSearchQuery(
            $this->searchEngine,
            $this->productManager->restrictSearchQuery($searchQuery)
        );
    }

    /**
     * @param array $orderings [field name => direction, ...]
     *
     * @return array [field name => direction, ...]
     */
    private function resolveOrderings(array $orderings): array
    {
        if (empty($orderings)) {
            return $orderings;
        }

        /**
         * * the search index sorts data by relevance by default,
         *   this type of sorting must not be specified explicitly
         * * sorting by relevance cannot be combined with sorting by other fields
         */
        $resolvedOrderings = [];
        $isSortingByRelevanceRequested = false;
        foreach ($orderings as $fieldName => $direction) {
            if (SetDefaultProductSearchSorting::RELEVANCE_SORT_FIELD !== $fieldName) {
                $resolvedOrderings[$fieldName] = $direction;
            } else {
                if (Criteria::DESC === $direction) {
                    throw new InvalidSorterException('Inverse sorting by relevance is not supported.');
                }
                $isSortingByRelevanceRequested = true;
            }
        }
        if ($isSortingByRelevanceRequested && !empty($resolvedOrderings)) {
            throw new InvalidSorterException('Sorting by relevance cannot be combined with sorting by other fields.');
        }

        return $resolvedOrderings;
    }

    private function getSortFilterName(RequestType $requestType, FilterValueAccessorInterface $filterValues): string
    {
        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getSortFilterName();
        $sortFilterValue = $filterValues->get($sortFilterName);
        if (null === $sortFilterValue) {
            return $sortFilterName;
        }

        return $sortFilterValue->getSourceKey();
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string[]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getSelectFieldNames(EntityDefinitionConfig $config): array
    {
        $selectFields = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $propertyPath = $field->getPropertyPath($fieldName);
            if (ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath && !isset($selectFields[$propertyPath])) {
                $selectFields[$propertyPath] = $fieldName;
            }

            $dependsOn = $field->getDependsOn();
            if (!empty($dependsOn)) {
                foreach ($dependsOn as $propertyPath) {
                    if (isset($selectFields[$propertyPath])) {
                        continue;
                    }
                    $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
                    if (!$fieldName) {
                        /**
                         * @see \Oro\Bundle\ProductBundle\Api\Processor\LoadProductSearchData::updateConfigAndMetadata
                         */
                        $fieldName = str_replace(ConfigUtil::PATH_DELIMITER, '_', $propertyPath);
                    }
                    $selectFields[$propertyPath] = $fieldName;
                }
            }
        }

        $result = [];
        foreach ($selectFields as $propertyPath => $fieldName) {
            $result[] = $propertyPath . ' as ' . $fieldName;
        }

        return $result;
    }
}
