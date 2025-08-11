<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Model\SearchResult;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads product search aggregated data using a search query result stored in the context.
 */
class LoadProductSearchAggregatedData implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        /** @var SearchResult|null $searchResult */
        $searchResult = $context->get(LoadProductSearchData::SEARCH_RESULT);
        if (null === $searchResult) {
            return;
        }

        try {
            $aggregatedData = $searchResult->getAggregatedData();
        } catch (InvalidSearchQueryException $e) {
            $error = Error::createValidationError(Constraint::FILTER, $e->getMessage());
            $aggregationFilterName = $this->getSearchAggregationFilterName($context);
            if ($aggregationFilterName) {
                $error->setSource(ErrorSource::createByParameter($aggregationFilterName));
            }
            $context->addError($error);

            return;
        }

        if ($aggregatedData) {
            $context->addInfoRecord('aggregatedData', $aggregatedData);
        }
    }

    private function getSearchAggregationFilterName(ListContext $context): ?string
    {
        $filterValues = $context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($context->getFilters()->get($filterKey) instanceof SearchAggregationFilter) {
                return $filterKey;
            }
        }

        return null;
    }
}
