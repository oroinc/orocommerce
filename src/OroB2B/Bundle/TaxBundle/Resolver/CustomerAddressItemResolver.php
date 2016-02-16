<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

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

        if ($taxable->getResult()->count() !== 0) {
            return;
        }

        $taxRules = $this->matcher->match($address, $this->getTaxCodes($taxable));
        $taxableAmount = BigDecimal::of($taxable->getPrice());

        $result = $taxable->getResult();
        $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableAmount);
        $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount, $taxable->getQuantity());
    }

    /**
     * @param Taxable $taxable
     * @return TaxCodes
     */
    protected function getTaxCodes(Taxable $taxable)
    {
        return TaxCodes::create(
            [
                TaxCode::create(
                    TaxCode::TYPE_PRODUCT,
                    $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE)
                ),
                TaxCode::create(
                    TaxCode::TYPE_ACCOUNT,
                    $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE)
                ),
            ]
        );
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }
}
