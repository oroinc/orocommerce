<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Resolver to switch taxation address to a customer's one for digital product
 */
class DigitalResolver implements ResolverInterface
{
    public function __construct(
        private ResolverInterface $resolver
    ) {
    }

    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        $itemsResult = [];
        foreach ($taxable->getItems() as $taxableItem) {
            $this->resolver->resolve($taxableItem);
            $itemsResult[] = $taxableItem->getResult();
        }

        $taxable->getResult()->offsetSet(Result::ITEMS, $itemsResult);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getItems()->count() &&
            !$taxable->isKitTaxable() &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getDestination() &&
            EuropeanUnionHelper::isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());
    }
}
