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
 * Resolver to apply taxes to shipping cost
 */
class ShippingResolver implements ResolverInterface
{
    use CalculateAdjustmentTrait;

    /**
     * @var TaxCalculatorInterface
     */
    protected $incTaxCalculator;

    /**
     * @var TaxCalculatorInterface
     */
    protected $excTaxCalculator;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    public function __construct(
        TaxCalculatorInterface $incTaxCalculator,
        TaxCalculatorInterface $excTaxCalculator,
        MatcherInterface $matcher,
        TaxationSettingsProvider $taxationSettingsProvider
    ) {
        $this->incTaxCalculator = $incTaxCalculator;
        $this->excTaxCalculator = $excTaxCalculator;
        $this->matcher = $matcher;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        if (null === $taxable->getShippingCost() || !$taxable->getShippingCost()->isPositiveOrZero()) {
            return;
        }

        $address = $taxable->getTaxationAddress();
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address, $this->getTaxCodes($taxable));

        $taxRate = BigDecimal::zero();
        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $taxableAmount = $taxable->getShippingCost();
        if ($this->taxationSettingsProvider->isShippingRatesIncludeTax()) {
            $resultElement = $this->incTaxCalculator->calculate($taxableAmount, $taxRate);
        } else {
            $resultElement = $this->excTaxCalculator->calculate($taxableAmount, $taxRate);
        }

        $this->calculateAdjustment($resultElement);

        $taxable->getResult()->offsetSet(Result::SHIPPING, $resultElement);
    }

    /**
     * @param Taxable $taxable
     * @return TaxCodes
     */
    public function getTaxCodes(Taxable $taxable)
    {
        $taxCodes = [];
        foreach ($this->taxationSettingsProvider->getShippingTaxCodes() as $shippingTaxCode) {
            $taxCodes[] = TaxCode::create($shippingTaxCode, TaxCodeInterface::TYPE_PRODUCT);
        }

        $customerContextCode = $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE);
        if (null !== $customerContextCode) {
            $taxCodes[] = TaxCode::create($customerContextCode, TaxCodeInterface::TYPE_ACCOUNT);
        }

        return TaxCodes::create($taxCodes);
    }
}
