<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigNumber;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\AbstractResult;
use OroB2B\Bundle\TaxBundle\Model\AbstractResultElement;

class RoundingResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();

        $this->walk($taxable->getResult());
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
    protected function round(AbstractResultElement $result)
    {
        foreach ($result as $key => $value) {
            try {
                $value = (string)BigNumber::of($value)
                    ->toScale(TaxCalculatorInterface::SCALE, RoundingMode::UP)
                    ->stripTrailingZeros();
            } catch (NumberFormatException $e) {
            }

            $result->offsetSet($key, $value);
        }
    }
}
