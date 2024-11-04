<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Resolver to apply taxes to shipping cost.
 */
class ShippingResolver implements ResolverInterface
{
    use TaxCalculateResolverTrait;

    public function __construct(
        private TaxCalculatorInterface $incTaxCalculator,
        private TaxCalculatorInterface $excTaxCalculator,
        private MatcherInterface $matcher,
        private TaxationSettingsProvider $taxationSettingsProvider
    ) {
    }

    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        $taxRules = $this->matcher->match($taxable->getTaxationAddress(), $this->getTaxCodes($taxable));

        $taxRate = BigDecimal::zero();
        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $taxableAmount = $taxable->getShippingCost();
        if ($this->taxationSettingsProvider->isShippingRatesIncludeTax($taxable->getContextValue('scopeValue'))) {
            $resultElement = $this->incTaxCalculator->calculate($taxableAmount, $taxRate);
        } else {
            $resultElement = $this->excTaxCalculator->calculate($taxableAmount, $taxRate);
        }

        $this->calculateAdjustment($resultElement);

        $taxable->getResult()->offsetSet(Result::SHIPPING, $resultElement);
    }

    protected function getTaxCodes(Taxable $taxable): TaxCodes
    {
        $taxCodes = [];
        $shippingTaxCodes = $this->taxationSettingsProvider->getShippingTaxCodes(
            $taxable->getContextValue('scopeValue')
        );

        foreach ($shippingTaxCodes as $shippingTaxCode) {
            $taxCodes[] = TaxCode::create($shippingTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }

        $customerContextCode = $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE);
        if (null !== $customerContextCode) {
            $taxCodes[] = TaxCode::create($customerContextCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        return TaxCodes::create($taxCodes);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getItems()->count() &&
            !$taxable->getResult()->isResultLocked() &&
            $taxable->getShippingCost()?->isPositiveOrZero() &&
            $taxable->getTaxationAddress();
    }
}
