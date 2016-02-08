<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class CustomerAddressItemResolver extends AbstractItemResolver
{
    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count()) {
            return;
        }

        if (!$taxable->getPrice()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $productTaxCode = $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE);

        $taxRules = $this->matcher->match($address, $productTaxCode);
        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $result = $taxable->getResult();
        $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
        $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount);
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }
}
