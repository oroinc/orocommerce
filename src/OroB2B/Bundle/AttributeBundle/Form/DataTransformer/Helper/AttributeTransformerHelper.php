<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer\Helper;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;

class AttributeTransformerHelper
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var DatabaseTransformerHelper
     */
    protected $databaseHelper;

    /**
     * @var DefaultsTransformerHelper
     */
    protected $defaultsHelper;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->databaseHelper = new DatabaseTransformerHelper($managerRegistry);
        $this->defaultsHelper = new DefaultsTransformerHelper($this->propertyAccessor, $this->databaseHelper);
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        return $this->propertyAccessor;
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    public function getLabels(Attribute $attribute)
    {
        $result = [];
        foreach ($attribute->getLabels() as $label) {
            $localeId = $label->getLocale() ? $label->getLocale()->getId() : null;
            $fallback = $label->getFallback();
            if ($fallback) {
                $result[$localeId] = new FallbackType($fallback);
            } else {
                $result[$localeId] = $label->getValue();
            }
        }

        return $result;
    }

    /**
     * @param Attribute $attribute
     * @param array $labels
     */
    public function setLabels(Attribute $attribute, array $labels)
    {
        foreach ($labels as $localeId => $label) {
            $attributeLabel = $attribute->getLabelByLocaleId($localeId);
            if (!$attributeLabel) {
                $attributeLabel = new AttributeLabel();
                if ($localeId) {
                    $attributeLabel->setLocale($this->databaseHelper->findLocale($localeId));
                }
                $attribute->addLabel($attributeLabel);
            }
            if ($label instanceof FallbackType) {
                $attributeLabel->setValue(null)
                    ->setFallback($label->getType());
            } else {
                $attributeLabel->setValue($label)
                    ->setFallback(null);
            }
        }
    }

    /**
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     * @return mixed
     */
    public function getDefaultValue(Attribute $attribute, AttributeTypeInterface $attributeType)
    {
        return $this->defaultsHelper->getDefaultValue($attribute, $attributeType);
    }

    /**
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     * @param mixed $value
     */
    public function setDefaultValue(Attribute $attribute, AttributeTypeInterface $attributeType, $value)
    {
        $this->defaultsHelper->setDefaultValue($attribute, $attributeType, $value);
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    public function getDefaultOptions(Attribute $attribute)
    {
        return $this->defaultsHelper->getDefaultOptions($attribute);
    }

    /**
     * @param Attribute $attribute
     * @param array $options
     */
    public function setDefaultOptions(Attribute $attribute, array $options)
    {
        $this->defaultsHelper->setDefaultOptions($attribute, $options);
    }

    /**
     * @param Attribute $attribute
     * @param string $field
     * @return array
     */
    public function getPropertyValues(Attribute $attribute, $field)
    {
        $result = [];
        foreach ($attribute->getPropertiesByField($field) as $property) {
            $websiteId = $property->getWebsite() ? $property->getWebsite()->getId() : null;
            $fallback = $property->getFallback();
            if ($fallback) {
                $result[$websiteId] = new FallbackType($fallback);
            } else {
                $result[$websiteId] = $property->isValue();
            }
        }

        return $result;
    }

    /**
     * @param Attribute $attribute
     * @param string $field
     * @param array $values
     */
    public function setPropertyValues(Attribute $attribute, $field, array $values)
    {
        foreach ($values as $websiteId => $value) {
            $attributeProperty = $attribute->getPropertyByFieldAndWebsiteId($field, $websiteId);
            if (!$attributeProperty) {
                $attributeProperty = new AttributeProperty();
                $attributeProperty->setField($field);
                if ($websiteId) {
                    $attributeProperty->setWebsite($this->databaseHelper->findWebsite($websiteId));
                }
                $attribute->addProperty($attributeProperty);
            }
            if ($value instanceof FallbackType) {
                $attributeProperty->setValue(null)
                    ->setFallback($value->getType());
            } else {
                $attributeProperty->setValue($value)
                    ->setFallback(null);
            }
        }
    }
}
