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

class RoundingResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        $this->walk($taxable->getResult());

        foreach ($taxable->getItems() as $taxableItem) {
            $this->walk($taxableItem->getResult());
        }
    }

    /**
     * @param AbstractResult|array $result
     */
    protected function walk($result)
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

    /**
     * @param AbstractResultElement $result
     */
    public function round(AbstractResultElement $result)
    {
        foreach ($result as $key => $value) {
            try {
                $value = BigDecimal::of($value);
            } catch (NumberFormatException $e) {
                continue;
            }

            if (!in_array($key, (array)$this->getExcludedKeys(), true)) {
                $value = $value->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
            }

            $result->offsetSet($key, $value->stripTrailingZeros());
        }
    }

    /**
     * @return array
     */
    protected function getExcludedKeys()
    {
        return [
            TaxResultElement::RATE, // we should not round rates
            ResultElement::ADJUSTMENT, // we should not round adjustments
        ];
    }
}
