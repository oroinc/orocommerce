<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\CatalogBundle\Api\Repository\CategoryNodeRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes IDs of master catalog tree node that are not available for the storefront
 * from a specific filter.
 */
class RemoveNotAvailableCategoryNodeFromFilter implements ProcessorInterface
{
    private string $filterKey;
    private CategoryNodeRepository $categoryNodeRepository;

    public function __construct(string $filterKey, CategoryNodeRepository $categoryNodeRepository)
    {
        $this->filterKey = $filterKey;
        $this->categoryNodeRepository = $categoryNodeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (null === $context->getCriteria()) {
            // the criteria object is already processed
            return;
        }

        $filterValues = $context->getFilterValues();
        $filterValue = $filterValues->get($this->filterKey);
        if (null === $filterValue) {
            // the filtering was not requested
            return;
        }

        $this->correctFilterValue($filterValue, $context);
    }

    private function correctFilterValue(FilterValue $filterValue, Context $context): void
    {
        $value = $filterValue->getValue();
        if ($value instanceof Range) {
            throw new RuntimeException('The filter does not support the range value.');
        }
        if (\is_bool($value)) {
            return;
        }

        $availableIds = $this->categoryNodeRepository->getAvailableCategoryNodeIds(
            (array)$value,
            $context->getConfig(),
            $context->getRequestType()
        );
        $numberOfAvailableIds = \count($availableIds);
        if ($numberOfAvailableIds > 1) {
            $filterValue->setValue($availableIds);
        } elseif (1 === $numberOfAvailableIds) {
            $filterValue->setValue(reset($availableIds));
        } else {
            $filterValue->setValue(-1);
        }
    }
}
