<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

class ProductVariantTypeHandlerRegistry
{
    /** @var ProductVariantTypeHandlerInterface[] */
    protected $typeHandlers = [];

    public function addHandler(ProductVariantTypeHandlerInterface $typeHandler)
    {
        $this->typeHandlers[$typeHandler->getType()] = $typeHandler;
    }

    /**
     * @param string $type
     * @return ProductVariantTypeHandlerInterface
     */
    public function getVariantTypeHandler($type)
    {
        if (!array_key_exists($type, $this->typeHandlers)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Handler for type "%s" was not found. Known types: %s',
                    (string)$type,
                    implode(', ', array_keys($this->typeHandlers))
                )
            );
        }

        return $this->typeHandlers[$type];
    }

    /**
     * @return ProductVariantTypeHandlerInterface[]
     */
    public function getVariantTypeHandlers()
    {
        return $this->typeHandlers;
    }
}
