<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting;

/**
 * Sets default sorting by relevance for ProductSearch entity.
 */
class SetDefaultProductSearchSorting extends SetDefaultSorting
{
    public const RELEVANCE_SORT_FIELD = 'relevance';

    #[\Override]
    protected function getDefaultValue(EntityDefinitionConfig $config, ?SortersConfig $configOfSorters): array
    {
        $orderBy = $config->getOrderBy();
        if (!$orderBy && $this->isSorterEnabled(self::RELEVANCE_SORT_FIELD, $configOfSorters)) {
            $orderBy = [self::RELEVANCE_SORT_FIELD => Criteria::ASC];
        }

        return $orderBy;
    }
}
