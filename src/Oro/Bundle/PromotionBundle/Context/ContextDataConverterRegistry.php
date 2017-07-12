<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class ContextDataConverterRegistry implements ContextDataConverterInterface
{
    /**
     * @var array|ContextDataConverterInterface[]
     */
    private $converters = [];

    /**
     * @param ContextDataConverterInterface $converter
     */
    public function registerConverter(ContextDataConverterInterface $converter)
    {
        $this->converters[] = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function getContextData($sourceEntity): array
    {
        $converter = $this->getConverterForEntity($sourceEntity);
        if ($converter === null) {
            throw new UnsupportedSourceEntityException();
        }

        return $converter->getContextData($sourceEntity);
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
     * @return null|ContextDataConverterInterface
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
