<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

class CustomerAddressItemResolver extends AbstractItemResolver
{
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

        if ($taxable->getResult()->isResultLocked()) {
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
        $taxCodes = [];

        $productContextCode = $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE);
        if (null !== $productContextCode) {
            $taxCodes[] = TaxCode::create($productContextCode, TaxCode::TYPE_PRODUCT);
        }

        $accountContextCode = $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE);
        if (null !== $accountContextCode) {
            $taxCodes[] = TaxCode::create($accountContextCode, TaxCode::TYPE_ACCOUNT);
        }

        return TaxCodes::create($taxCodes);
    }
}
