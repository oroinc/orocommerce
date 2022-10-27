<?php

namespace Oro\Bundle\PricingBundle\Provider;

/**
 * Provide Combined Price List identifier based on given relations
 */
interface CombinedPriceListIdentifierProviderInterface
{
    public const GLUE = '_';

    /**
     * @param PriceListSequenceMember[] $priceListsRelations
     * @return string
     */
    public function getCombinedPriceListIdentifier(array $priceListsRelations): string;
}
