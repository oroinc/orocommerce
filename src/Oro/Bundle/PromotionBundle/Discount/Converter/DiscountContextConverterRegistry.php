<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class DiscountContextConverterRegistry implements DiscountContextConverterInterface
{
    /**
     * @var array|DiscountContextConverterInterface[]
     */
    private $converters = [];

    /**
     * @param DiscountContextConverterInterface $converter
     */
    public function registerConverter(DiscountContextConverterInterface $converter)
    {
        $this->converters[] = $converter;
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
     * @return null|DiscountContextConverterInterface
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
