<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Model\AbstractResult;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Round the taxable amounts.
 */
class RoundingResolver implements ResolverInterface
{
    public function resolve(Taxable $taxable): void
    {
        $this->walk($taxable->getResult());

        foreach ($taxable->getItems() as $taxableItem) {
            $this->walk($taxableItem->getResult());

            if ($taxableItem->isKitTaxable()) {
                foreach ($taxableItem->getItems() as $taxableKitItem) {
                    $this->walk($taxableKitItem->getResult());
                }
            }
        }
    }

    /**
     * @param AbstractResult|array $result
     */
    protected function walk($result): void
    {
        if ($result instanceof AbstractResultElement) {
            $this->round($result);
        }

        if (is_array($result) || $result instanceof \Traversable) {
            foreach ($result as $resultItem) {
                $this->walk($resultItem);
            }
        }
    }

    public function round(AbstractResultElement $result): void
    {
        foreach ($result as $key => $value) {
            try {
                $value = BigDecimal::of($value);
            } catch (NumberFormatException $e) {
                continue;
            }

            if (!in_array($key, $this->getExcludedKeys(), true)) {
                $value = $value->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
            }

            $result->offsetSet($key, $value->stripTrailingZeros());
        }
    }

    protected function getExcludedKeys(): array
    {
        return [
            TaxResultElement::RATE, // we should not round rates
            ResultElement::ADJUSTMENT, // we should not round adjustments
        ];
    }
}
