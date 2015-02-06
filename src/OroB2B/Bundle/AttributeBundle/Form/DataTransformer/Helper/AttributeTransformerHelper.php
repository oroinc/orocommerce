<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer\Helper;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class AttributeTransformerHelper
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param int $localeId
     * @return Locale|null
     */
    protected function findLocale($localeId)
    {
        return $this->managerRegistry->getRepository('OroB2BWebsiteBundle:Locale')->find($localeId);
    }

    /**
     * @param int $websiteId
     * @return Website|null
     */
    protected function findWebsite($websiteId)
    {
        return $this->managerRegistry->getRepository('OroB2BWebsiteBundle:Website')->find($websiteId);
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
                    $attributeLabel->setLocale($this->findLocale($localeId));
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
        $dataField = $attributeType->getDataTypeField();
        $result = null;

        if ($attribute->isLocalized()) {
            $result = [];
            foreach ($attribute->getDefaultValues() as $defaultValue) {
                $localeId = $defaultValue->getLocale() ? $defaultValue->getLocale()->getId() : null;
                $fallback = $defaultValue->getFallback();
                if ($fallback) {
                    $result[$localeId] = new FallbackType($fallback);
                } else {
                    $result[$localeId]
                        = $attributeType->normalize($this->getDefaultValueByField($defaultValue, $dataField));
                }
            }
        } else {
            $defaultValue = $attribute->getDefaultValueByLocaleId(null);
            $result = $attributeType->normalize($this->getDefaultValueByField($defaultValue, $dataField));
        }

        return $result;
    }

    /**
     * @param AttributeDefaultValue|null $defaultValue
     * @param string $field
     * @return mixed
     */
    protected function getDefaultValueByField($defaultValue, $field)
    {
        $accessor = $this->getPropertyAccessor();
        if ($defaultValue) {
            return $accessor->getValue($defaultValue, $field);
        } else {
            return null;
        }
    }

    /**
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     * @param mixed $value
     */
    public function setDefaultValue(Attribute $attribute, AttributeTypeInterface $attributeType, $value)
    {
        if ($attribute->isLocalized()) {
            foreach ($value as $localeId => $itemValue) {
                $this->setDefaultValueByField($attribute, $attributeType, $localeId, $itemValue);
            }
        } else {
            $this->setDefaultValueByField($attribute, $attributeType, null, $value);
        }
    }

    /**
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     * @param int|null $localeId
     * @param mixed $value
     */
    protected function setDefaultValueByField(
        Attribute $attribute,
        AttributeTypeInterface $attributeType,
        $localeId,
        $value
    ) {
        $accessor = $this->getPropertyAccessor();
        $field = $attributeType->getDataTypeField();

        $defaultValue = $attribute->getDefaultValueByLocaleId($localeId);
        if (!$defaultValue) {
            $defaultValue = new AttributeDefaultValue();
            if ($localeId) {
                $defaultValue->setLocale($this->findLocale($localeId));
            }
            $attribute->addDefaultValue($defaultValue);
        }

        if ($value instanceof FallbackType) {
            $accessor->setValue($defaultValue, $field, null);
            $defaultValue->setFallback($value->getType());
        } else {
            $accessor->setValue($defaultValue, $field, $attributeType->denormalize($value));
            $defaultValue->setFallback(null);
        }
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
                    $attributeProperty->setWebsite($this->findWebsite($websiteId));
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
