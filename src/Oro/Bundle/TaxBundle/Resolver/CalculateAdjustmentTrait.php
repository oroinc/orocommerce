<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

trait CalculateAdjustmentTrait
{
    /**
     * @param AbstractResultElement $resultElement
     */
    protected function calculateAdjustment(AbstractResultElement $resultElement)
    {
        $taxAmount = BigDecimal::of($resultElement->getTaxAmount());
        $taxAmountRounded = $taxAmount->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
        $adjustment = $taxAmount->minus($taxAmountRounded);
        $resultElement->setAdjustment($adjustment);
    }
}
