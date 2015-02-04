<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use Symfony\Component\Translation\TranslatorInterface;

class AttributeTypeRegistry
{
    /**
     * Collection of attribute types
     *
     * @var AttributeTypeInterface[]
     */
    protected $attributeTypes;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $typeChoices = [];

    /**
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Add form type to registry
     *
     * @param AttributeTypeInterface $attributeType
     */
    public function addType(AttributeTypeInterface $attributeType)
    {
        $typeName = $attributeType->getName();
        $this->attributeTypes[$typeName] = $attributeType;
        $this->addTypeChoice($attributeType, $typeName);
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

    /**
     * @return array ['sometype' => 'translated label',...]
     */
    public function getChoices()
    {
        return $this->typeChoices;
    }

    /**
     * @param \OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface $attributeType
     * @param string $typeName [optional]
     */
    private function addTypeChoice(AttributeTypeInterface $attributeType, $typeName = '')
    {
        // TODO: shouldn't it be the responsibility of the attribute type to provide proper label key?
        $typeLabelPrefix = 'orob2b.attribute.attribute.type.';
        $typeLabel = $this->translator->trans($typeLabelPrefix . $typeName);

        $this->typeChoices[$typeName] = $typeLabel;
    }
}
