<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Helper\AmountNormalizerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

abstract class AbstractApruveEntityFactory
{
    /**
     * @var AmountNormalizerInterface
     */
    protected $amountNormalizer;

    /**
     * @param AmountNormalizerInterface $amountNormalizer
     */
    public function __construct(AmountNormalizerInterface $amountNormalizer)
    {
        $this->amountNormalizer = $amountNormalizer;
    }

    /**
     * @param float|int|string $amount
     *
     * @return int
     */
    protected function normalizeAmount($amount)
    {
        return $this->amountNormalizer->normalize($amount);
    }

    /**
     * @param Price $price
     *
     * @return int
     */
    protected function normalizePrice(Price $price)
    {
        return $this->normalizeAmount($price->getValue());
    }
}
