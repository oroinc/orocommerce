<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

/**
 * The registry of context data converters.
 */
class ContextDataConverterRegistry implements ContextDataConverterInterface
{
    /** @var array|ContextDataConverterInterface[] */
    private $converters;

    /**
     * @param iterable|ContextDataConverterInterface[] $converters
     */
    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
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
     *
     * @return ContextDataConverterInterface|null
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
