<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

class ProductVariantFieldValueHandlerRegistry
{
    /** @var ProductVariantFieldValueHandlerInterface[] */
    private $variantFieldValueHandlers = [];

    public function addHandler(ProductVariantFieldValueHandlerInterface $variantFieldValueHandler)
    {
        $this->variantFieldValueHandlers[$variantFieldValueHandler->getType()] = $variantFieldValueHandler;
    }

    /**
     * @param string $type
     * @return ProductVariantFieldValueHandlerInterface
     * @throws \InvalidArgumentException
     */
    public function getVariantFieldValueHandler($type)
    {
        if (!array_key_exists($type, $this->variantFieldValueHandlers)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value handler "%s" for variant field was not found. Known types: %s',
                    (string)$type,
                    implode(', ', array_keys($this->variantFieldValueHandlers))
                )
            );
        }

        return $this->variantFieldValueHandlers[$type];
    }

    /**
     * @return ProductVariantFieldValueHandlerInterface[]
     */
    public function getVariantFieldValueHandlers()
    {
        return $this->variantFieldValueHandlers;
    }
}
