<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductKitPrice;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds dynamic kitItem product and quantity filters to the 'productkitprices' resource.
 */
class AddKitItemFilters implements ProcessorInterface
{
    private const string KIT_ITEM_FILTER_PATTERN = '/kitItems\.(\d+)\.(product|quantity)/';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $filterCollection = $context->getFilters();
        $filters = $context->getFilterValues()->getAll();

        foreach ($filters as $filterKey => $filter) {
            if (self::isKitItemFilter($filterKey)) {
                $filter = \reset($filter);
                $filterCollection->add($filterKey, new StandaloneFilter($this->getDataType($filter->getPath())), false);
            }
        }
    }

    public static function isKitItemFilter(string $path): bool
    {
        return \preg_match(self::KIT_ITEM_FILTER_PATTERN, $path);
    }

    private function getDataType(string $path): string
    {
        [,, $field] = \explode('.', $path);

        return $field === 'product' ? DataType::INTEGER : DataType::FLOAT;
    }
}
