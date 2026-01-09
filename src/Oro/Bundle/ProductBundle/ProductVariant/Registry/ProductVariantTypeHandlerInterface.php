<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

use Symfony\Component\Form\FormInterface;

/**
 * Defines the contract for creating forms for product variant field types.
 *
 * Implementations of this interface provide type-specific form creation logic for variant fields,
 * generating appropriate form fields based on the field type and available variant values.
 */
interface ProductVariantTypeHandlerInterface
{
    /**
     * @param string $fieldName
     * @param array $availability
     * @param array $options
     * @return FormInterface
     */
    public function createForm($fieldName, array $availability, array $options = []);

    /**
     * @return string
     */
    public function getType();
}
