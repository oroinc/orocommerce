<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

/**
 * Registry for product variant type handlers.
 *
 * This registry manages handlers that create forms for different variant field types,
 * providing a centralized way to retrieve the appropriate handler based on field type
 * when building variant selection interfaces.
 */
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
