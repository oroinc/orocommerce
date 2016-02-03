<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;
use OroB2B\Bundle\TaxBundle\Matcher\RegionMatcher;
use OroB2B\Bundle\TaxBundle\Resolver\StopPropagationException;

class USSalesTaxDigitalResolver implements ResolverInterface
{
    /** @var ResolverInterface */
    protected $itemResolver;

    /** @var RegionMatcher */
    protected $matcher;

    /**
     * @param ResolverInterface $itemResolver
     * @param RegionMatcher $matcher
     */
    public function __construct(
        ResolverInterface $itemResolver,
        RegionMatcher $matcher
    ) {
        $this->itemResolver = $itemResolver;
        $this->matcher = $matcher;
    }

    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        if (!$this->matcher->isStateWithNonTaxableDigitals($taxable->getDestination())) {
            return;
        }

        $itemsResult = [];
        foreach ($taxable->getItems() as $taxableItem) {
            $this->itemResolver->resolve($taxableItem);
            $itemsResult[] = $taxableItem->getResult();
        }

        $taxable->getResult()->offsetSet(Result::ITEMS, $itemsResult);

        throw new StopPropagationException();
    }
}
