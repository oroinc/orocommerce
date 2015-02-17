<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

class AttributeTypeRegistry
{
    /**
     * Collection of attribute types
     *
     * @var AttributeTypeInterface[]
     */
    protected $attributeTypes;

    /**
     * Add form type to registry
     *
     * @param AttributeTypeInterface $attributeType
     */
    public function addType(AttributeTypeInterface $attributeType)
    {
        $typeName = $attributeType->getName();
        $this->attributeTypes[$typeName] = $attributeType;
    }

    /**
     * Get all registered attribute type
     *
     * @return AttributeTypeInterface[]
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
