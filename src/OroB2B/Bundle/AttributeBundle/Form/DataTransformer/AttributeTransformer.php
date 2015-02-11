<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\Helper\AttributeTransformerHelper;

class AttributeTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    protected $plainFields = [
        'code',
        'type',
        'localized',
        'containHtml',
        'sharingType',
        'required',
        'unique',
        'validation',
        'containHtml',
    ];

    /**
     * @var array
     */
    protected $websiteFields = [
        'onProductView' => [
            'name' => AttributeProperty::FIELD_ON_PRODUCT_VIEW,
            'default' => true,
        ],
        'inProductListing' => [
            'name' => AttributeProperty::FIELD_IN_PRODUCT_LISTING,
            'default' => false,
        ],
        'useInSorting' => [
            'name' => AttributeProperty::FIELD_USE_IN_SORTING,
            'default' => false,
        ],
        'onAdvancedSearch' => [
            'name' => AttributeProperty::FIELD_ON_ADVANCED_SEARCH,
            'default' => false,
        ],
        'onProductComparison' => [
            'name' => AttributeProperty::FIELD_ON_PRODUCT_COMPARISON,
            'default' => false,
        ],
        'useForSearch' => [
            'name' => AttributeProperty::FIELD_USE_FOR_SEARCH,
            'default' => false,
        ],
        'useInFilters' => [
            'name' => AttributeProperty::FIELD_USE_IN_FILTERS,
            'default' => false,
        ]
    ];

    /**
     * @var AttributeTransformerHelper
     */
    protected $helper;

    /**
     * @var AttributeTypeInterface
     */
    protected $typeRegistry;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param Attribute $attribute
     * @param AttributeTypeRegistry $typeRegistry
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        AttributeTypeRegistry $typeRegistry,
        Attribute $attribute
    ) {
        $this->helper = new AttributeTransformerHelper($managerRegistry);
        $this->typeRegistry = $typeRegistry;
        $this->attribute = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return null;
        }

        if (!$value instanceof Attribute) {
            throw new UnexpectedTypeException($value, 'Attribute');
        }

        $type = $this->getAttributeType($value);
        $accessor = $this->helper->getPropertyAccessor();
        $result = [];

        foreach ($this->plainFields as $field) {
            $result[$field] = $accessor->getValue($value, $field);
        }

        $result['label'] = $this->helper->getLabels($value);
        $result['defaultValue'] = $this->helper->getDefaultValue($value, $type);

        foreach ($this->websiteFields as $field => $data) {
            $propertyData = $this->helper->getPropertyValues($value, $data['name']);
            if (!array_key_exists(null, $propertyData)) {
                $propertyData[null] = $data['default'];
            }
            $result[$field] = $propertyData;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $type = $this->getAttributeType($this->attribute);
        $accessor = $this->helper->getPropertyAccessor();

        foreach ($this->plainFields as $field) {
            if (array_key_exists($field, $value)) {
                $accessor->setValue($this->attribute, $field, $value[$field]);
            }
        }

        if (array_key_exists('label', $value)) {
            $this->helper->setLabels($this->attribute, $value['label']);
        }

        if (array_key_exists('defaultValue', $value)) {
            $this->helper->setDefaultValue($this->attribute, $type, $value['defaultValue']);
        }

        foreach ($this->websiteFields as $field => $data) {
            if (array_key_exists($field, $value)) {
                $this->helper->setPropertyValues($this->attribute, $data['name'], $value[$field]);
            }
        }

        return $this->attribute;
    }

    /**
     * @param Attribute $attribute
     * @return AttributeTypeInterface
     */
    protected function getAttributeType(Attribute $attribute)
    {
        $type = $attribute->getType();
        if (!$type) {
            throw new TransformationFailedException('Attribute type is not defined');
        }

        $typeObject = $this->typeRegistry->getTypeByName($type);
        if (!$typeObject) {
            throw new TransformationFailedException(sprintf('Unknown attribute type "%s"', $type));
        }

        return $typeObject;
    }
}
