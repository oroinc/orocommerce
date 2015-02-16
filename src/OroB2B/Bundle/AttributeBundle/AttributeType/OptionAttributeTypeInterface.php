<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

interface OptionAttributeTypeInterface extends AttributeTypeInterface
{
    /**
     * Gets form parameters for default value
     * Key 'type' is required, key 'options' is optional
     * e.g. [
     *      'type'  => 'integer',
     *      'options' => [
     *          'data' => 0,
     *          'precision' => 0
     *      ]
     * ]
     *
     * @param Attribute $attribute
     * @return array
     */
    public function getDefaultValueFormParameters(Attribute $attribute);
}
