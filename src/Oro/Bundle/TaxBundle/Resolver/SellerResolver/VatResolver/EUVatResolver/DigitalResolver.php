<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;

/**
 * Resolver to switch taxation address to a customer's one for digital product
 */
class DigitalResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count() === 0) {
            return;
        }

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $buyerAddress = $taxable->getDestination();
        if (!$buyerAddress) {
            return;
        }

        $isBuyerFromEU = EuropeanUnionHelper::isEuropeanUnionCountry($buyerAddress->getCountryIso2());

        if (!$isBuyerFromEU) {
            return;
        }

        foreach ($taxable->getItems() as $item) {
            $this->resolver->resolve($item);
        }
    }
}
