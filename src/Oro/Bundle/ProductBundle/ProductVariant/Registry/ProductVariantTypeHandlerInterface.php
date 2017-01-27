<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\Registry;

use Symfony\Component\Form\FormInterface;

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
