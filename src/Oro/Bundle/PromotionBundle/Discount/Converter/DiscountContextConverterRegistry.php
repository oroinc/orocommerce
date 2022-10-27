<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

/**
 * The registry of discount context converters.
 */
class DiscountContextConverterRegistry implements DiscountContextConverterInterface
{
    /** @var iterable|DiscountContextConverterInterface[] */
    private $converters;

    /**
     * @param iterable|DiscountContextConverterInterface[] $converters
     */
    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        $converter = $this->getConverterForEntity($sourceEntity);
        if ($converter === null) {
            throw new UnsupportedSourceEntityException();
        }

        return $converter->convert($sourceEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $this->getConverterForEntity($sourceEntity) !== null;
    }

    /**
     * @param object $sourceEntity
     *
     * @return DiscountContextConverterInterface|null
     */
    private function getConverterForEntity($sourceEntity)
    {
        foreach ($this->converters as $converter) {
            if ($converter->supports($sourceEntity)) {
                return $converter;
            }
        }

        return null;
    }
}
