<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Provide basic methods for calculating and merging tax results.
 */
trait TaxCalculateResolverTrait
{
    protected function calculateAdjustment(ResultElement $resultElement): void
    {
        $taxAmount = BigDecimal::of($resultElement->getTaxAmount());
        $taxAmountRounded = $taxAmount->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
        $adjustment = $taxAmount->minus($taxAmountRounded);
        $resultElement->setAdjustment($adjustment);
    }

    protected function mergeData(ResultElement $target, ResultElement $source): ResultElement
    {
        $currentData = new ResultElement($target->getArrayCopy());

        foreach ($source as $key => $value) {
            if ($currentData->offsetExists($key)) {
                $currentValue = BigDecimal::of($currentData->offsetGet($key));
                $currentValue = $currentValue->plus($value);
                $currentData->offsetSet($key, (string)$currentValue);
            }
        }

        return $currentData;
    }
}
