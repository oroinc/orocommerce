<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver;

use OroB2B\Bundle\TaxBundle\Matcher\UnitedStatesHelper;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;

class USSalesTaxDigitalResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    protected $itemResolver;

    /**
     * @param ResolverInterface $itemResolver
     */
    public function __construct(ResolverInterface $itemResolver)
    {
        $this->itemResolver = $itemResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $isStateWithoutDigitalTax = UnitedStatesHelper::isStateWithoutDigitalTax(
            $address->getCountry()->getIso2Code(),
            $address->getRegion()->getCode()
        );

        if ($isStateWithoutDigitalTax) {
            foreach ($taxable->getItems() as $taxableItem) {
                $this->itemResolver->resolve($taxableItem);
            }
        }
    }
}
