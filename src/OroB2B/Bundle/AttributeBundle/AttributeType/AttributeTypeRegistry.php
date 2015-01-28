<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class AttributeTypeRegistry
{
    /**
     * Collection of attribute types
     *
     * @var array
     */
    protected $attributeTypes;

    /**
     * Add form type to registry
     *
     * @param AttributeTypeInterface $attributeType
     */
    public function addType(AttributeTypeInterface $attributeType)
    {
        $this->attributeTypes[$attributeType->getName()] = $attributeType;
    }

    /**
     * Get all registered attribute type
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->attributeTypes;
    }

    /**
     * @param string $name
     * @return AttributeTypeInterface|null
     */
    public function getTypeByName($name)
    {
        return isset($this->attributeTypes[$name]) ? $this->attributeTypes[$name] : null;
    }
}
