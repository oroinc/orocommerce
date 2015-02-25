<?php

namespace OroB2B\Bundle\AttributeBundle\Form\DataTransformer\Helper;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;

class DefaultsTransformerHelper
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
     * @param PropertyAccessor $propertyAccessor
     * @param DatabaseTransformerHelper $databaseHelper
     */
    public function __construct(PropertyAccessor $propertyAccessor, DatabaseTransformerHelper $databaseHelper)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * @param Attribute $attribute
     * @param AttributeTypeInterface $attributeType
     * @return mixed
     */
    public function getDefaultValue(Attribute $attribute, AttributeTypeInterface $attributeType)
    {
        $dataField = $attributeType->getDataTypeField();

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
        if ($defaultValue) {
            return $this->propertyAccessor->getValue($defaultValue, $field);
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
        $isLocalizedValue = is_array($value) && array_key_exists(null, $value);

        // normalize value
        if ($isLocalizedValue && !$attribute->isLocalized()) {
            $value = $value[null];
        } elseif (!$isLocalizedValue && $attribute->isLocalized()) {
            $value = [null => $value];
        }

        // set value
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
        $field = $attributeType->getDataTypeField();

        $defaultValue = $attribute->getDefaultValueByLocaleId($localeId);
        if (!$defaultValue) {
            $defaultValue = new AttributeDefaultValue();
            if ($localeId) {
                $defaultValue->setLocale($this->databaseHelper->findLocale($localeId));
            }
            $attribute->addDefaultValue($defaultValue);
        }

        if ($value instanceof FallbackType) {
            $this->propertyAccessor->setValue($defaultValue, $field, null);
            $defaultValue->setFallback($value->getType());
        } else {
            $this->propertyAccessor->setValue($defaultValue, $field, $attributeType->denormalize($value));
            $defaultValue->setFallback(null);
        }
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    public function getDefaultOptions(Attribute $attribute)
    {
        $result = [];
        foreach ($attribute->getOptions() as $option) {
            $masterOptionId = $this->getMasterOptionId($option);
            $localeId = $this->getOptionLocaleId($option);

            // if master option
            if ($option->getId() == $masterOptionId) {
                $result[$masterOptionId]['master_option_id'] = $masterOptionId;
                $result[$masterOptionId]['order'] = $option->getOrder();
            }

            $fallback = $option->getFallback();
            if ($fallback) {
                $value = new FallbackType($fallback);
            } else {
                $value = $option->getValue();
            }

            $result[$masterOptionId]['data'][$localeId] = ['value' => $value, 'is_default' => false];
        }

        foreach ($attribute->getDefaultValues() as $defaultValue) {
            $option = $defaultValue->getOption();
            if (!$option) {
                continue;
            }

            $masterOptionId = $this->getMasterOptionId($option);
            $localeId = $this->getOptionLocaleId($option);
            $result[$masterOptionId]['data'][$localeId]['is_default'] = true;
        }

        usort($result, function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return $a['order'] > $b['order'] ? 1 : -1;
        });

        return $result;
    }

    /**
     * @param Attribute $attribute
     * @param array $options
     */
    public function setDefaultOptions(Attribute $attribute, array $options)
    {
        $attributeOptions = [];
        $attributeDefaultValues = [];

        foreach ($options as $optionData) {
            $order = $this->getOption($optionData, 'order', 0);
            $data = $this->getOption($optionData, 'data', []);
            $masterOptionId = $this->getOption($optionData, 'master_option_id');

            $masterOption = $attribute->getOptionById($masterOptionId);
            if (!$masterOption) {
                $masterOption = new AttributeOption();
                $attribute->addOption($masterOption);
            }
            foreach ($data as $localeId => $localeData) {
                $localeValue = $this->getOption($localeData, 'value', '');

                $option = $this->generateOption($masterOption, $localeId, $localeValue, $order);
                $attributeOptions[] = $option;

                if (!empty($localeData['is_default'])) {
                    $attributeDefaultValues[] = $this->generateOptionDefaultValue($attribute, $option, $localeId);
                }
            }
        }

        $this->propertyAccessor->setValue($attribute, 'options', $attributeOptions);
        $this->propertyAccessor->setValue($attribute, 'defaultValues', $attributeDefaultValues);
    }

    /**
     * @param AttributeOption $masterOption
     * @param int $localeId
     * @param string|FallbackType $value
     * @param int $order
     * @return null|AttributeOption
     */
    protected function generateOption(AttributeOption $masterOption, $localeId, $value, $order)
    {
        if ($localeId) {
            $option = $masterOption->getRelatedOptionByLocaleId($localeId);
            if (!$option) {
                $option = new AttributeOption();
                $option->setLocale($this->databaseHelper->findLocale($localeId));
                $masterOption->addRelatedOption($option);
            }
        } else {
            $option = $masterOption;
        }

        if ($value instanceof FallbackType) {
            $option->setFallback($value->getType())
                ->setValue(null);
        } else {
            $option->setFallback(null)
                ->setValue($value);
        }

        $option->setOrder($order);

        return $option;
    }

    /**
     * @param Attribute $attribute
     * @param AttributeOption $option
     * @param int $localeId
     * @return AttributeDefaultValue
     */
    protected function generateOptionDefaultValue(Attribute $attribute, AttributeOption $option, $localeId)
    {
        $defaultValue = null;

        if ($option->getId()) {
            $defaultValue = $attribute->getDefaultValueByLocaleIdAndOptionId($localeId, $option->getId());
        }

        if (!$defaultValue) {
            $defaultValue = new AttributeDefaultValue();
            if ($localeId) {
                $defaultValue->setLocale($this->databaseHelper->findLocale($localeId));
            }
        }

        $defaultValue->setOption($option);

        return $defaultValue;
    }

    /**
     * @param AttributeOption $option
     * @return int
     */
    protected function getMasterOptionId(AttributeOption $option)
    {
        return $option->getMasterOption() ? $option->getMasterOption()->getId() : $option->getId();
    }

    /**
     * @param AttributeOption $option
     * @return int|null
     */
    protected function getOptionLocaleId(AttributeOption $option)
    {
        return $option->getLocale() ? $option->getLocale()->getId() : null;
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(array $array, $key, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }
}
