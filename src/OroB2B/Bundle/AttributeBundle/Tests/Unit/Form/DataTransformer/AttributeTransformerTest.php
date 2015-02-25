<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\Select;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeOption;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeTransformer;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Model\SharingType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AttributeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Locale[]
     */
    protected $locales = [];

    /**
     * @var Website[]
     */
    protected $websites = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeRegistry
     */
    protected $typeRegistry;

    protected function setUp()
    {
        $localeRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $localeRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap([[1, $this->getLocale(1)], [2, $this->getLocale(2)]]));

        $websiteRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $websiteRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap([[1, $this->getWebsite(1)], [2, $this->getWebsite(2)]]));

        $repositoriesMap = [
            ['OroB2BWebsiteBundle:Locale', null, $localeRepository],
            ['OroB2BWebsiteBundle:Website', null, $websiteRepository],
        ];

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap($repositoriesMap));

        $this->typeRegistry = new AttributeTypeRegistry();
        $this->typeRegistry->addType(new Text());
        $this->typeRegistry->addType(new Integer());
        $this->typeRegistry->addType(new Select());
    }

    /**
     * @param Attribute|null $model
     * @param array|null $view
     * @dataProvider transformDataProvider
     */
    public function testTransform($model, $view)
    {
        $transformer = new AttributeTransformer($this->managerRegistry, $this->typeRegistry, new Attribute());
        $this->assertEquals($view, $transformer->transform($model));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function transformDataProvider()
    {
        $emptyAttribute = new Attribute();
        $emptyAttribute->setCode('empty')
            ->setType(Text::NAME);

        $fullAttribute = $this->getFullAttribute();

        $fullView = $this->getFullView();
        $fullView = array_merge($fullView, ['useForSearch' => [null => false]]);

        $optionAttribute = $this->getOptionAttribute();
        $optionAttribute->addDefaultValue($this->createDefaultValue(null));

        $optionView = $this->getOptionView();
        $optionView = array_merge($optionView, ['useForSearch' => [null => false]]);

        return [
            'null' => [
                'model' => null,
                'view' => null,
            ],
            'empty not localized attribute' => [
                'model' => $emptyAttribute,
                'view' => [
                    'code' => 'empty',
                    'type' => Text::NAME,
                    'localized' => false,
                    'containHtml' => false,
                    'sharingType' => null,
                    'required' => false,
                    'unique' => false,
                    'validation' => null,
                    'label' => [],
                    'defaultValue' => null,
                    'onProductView' => [null => true],
                    'inProductListing' => [null => false],
                    'useInSorting' => [null => false],
                    'onAdvancedSearch' => [null => false],
                    'onProductComparison' => [null => false],
                    'useForSearch' => [null => false],
                    'useInFilters' => [null => false],
                ],
            ],
            'full localized attribute' => [
                'model' => $fullAttribute,
                'view' => $fullView,
            ],
            'option attribute' => [
                'model' => $optionAttribute,
                'view' => $optionView,
            ],
        ];
    }

    /**
     * @param array|null $view
     * @param Attribute|null $model
     * @param Attribute|null $attribute
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($view, $model, Attribute $attribute = null)
    {
        if (!$attribute) {
            $attribute = new Attribute();
        }

        $transformer = new AttributeTransformer($this->managerRegistry, $this->typeRegistry, $attribute);
        $actualModel = $transformer->reverseTransform($view);

        if ($model && $actualModel) {
            // collections should be compared without keys influence
            $model->resetLabels($model->getLabels()->toArray());
            $model->resetProperties($model->getProperties()->toArray());
            $model->resetDefaultValues($model->getDefaultValues()->toArray());
            $model->resetOptions($model->getOptions()->toArray());
            $actualModel->resetLabels($actualModel->getLabels()->toArray());
            $actualModel->resetProperties($actualModel->getProperties()->toArray());
            $actualModel->resetDefaultValues($actualModel->getDefaultValues()->toArray());
            $actualModel->resetOptions($actualModel->getOptions()->toArray());
        }

        $this->assertEquals($model, $actualModel);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function reverseTransformDataProvider()
    {
        // full attribute data
        $fullAttribute = $this->getFullAttribute();

        $fullInputAttribute = new Attribute();
        $fullInputAttribute->setType(Integer::NAME);

        // localized attribute data
        $localizedAttribute = new Attribute();
        $localizedAttribute
            ->setCode('localized')
            ->setType(Text::NAME)
            ->setLocalized(true)
            ->addLabel($this->createLabel(null, 'localized_label'))
            ->addDefaultValue($this->createDefaultValue(null, null)->setText('localized_value'));

        $localizedInputAttribute = new Attribute();
        $localizedInputAttribute->setType(Text::NAME);

        // not localized attribute data
        $notLocalizedAttribute = new Attribute();
        $notLocalizedAttribute
            ->setCode('not_localized')
            ->setType(Text::NAME)
            ->setLocalized(false)
            ->addLabel($this->createLabel(null, 'not_localized_label'))
            ->addDefaultValue($this->createDefaultValue(null, null)->setText('not_localized_value'));

        $notLocalizedInputAttribute = new Attribute();
        $notLocalizedInputAttribute->setType(Text::NAME);

        $fullView = $this->getFullView();

        // option attribute data
        $optionAttribute = $this->getOptionAttribute();

        $optionInputAttribute = new Attribute();
        $optionInputAttribute->setType(Select::NAME);
        $optionInputAttribute->resetOptions($optionAttribute->getOptions());

        $optionView = $this->getOptionView();

        return [
            'null' => [
                'view'  => null,
                'model' => null,
            ],
            'full localized attribute' => [
                'view'  => $fullView,
                'model' => $fullAttribute,
                'attribute' => $fullInputAttribute,
            ],
            'normalize from not localized to localized' => [
                'view'  => [
                    'code' => 'localized',
                    'type' => Text::NAME,
                    'localized' => true,
                    'containHtml' => false,
                    'sharingType' => null,
                    'required' => false,
                    'unique' => false,
                    'validation' => null,
                    'label' => [
                        null => 'localized_label',
                    ],
                    'defaultValue' => 'localized_value',
                ],
                'model' => $localizedAttribute,
                'attribute' => $localizedInputAttribute,
            ],
            'normalize from localized to not localized' => [
                'view'  => [
                    'code' => 'not_localized',
                    'type' => Text::NAME,
                    'localized' => false,
                    'containHtml' => false,
                    'sharingType' => null,
                    'required' => false,
                    'unique' => false,
                    'validation' => null,
                    'label' => [
                        null => 'not_localized_label',
                    ],
                    'defaultValue' => [
                        null => 'not_localized_value'
                    ],
                ],
                'model' => $notLocalizedAttribute,
                'attribute' => $notLocalizedInputAttribute,
            ],
            'option attribute' => [
                'view'  => $optionView,
                'model' => $optionAttribute,
                'attribute' => $optionInputAttribute,
            ],
        ];
    }

    /**
     * @param string $exception
     * @param string $message
     * @param mixed $value
     * @dataProvider transformExceptionDataProvider
     */
    public function testTransformException($exception, $message, $value)
    {
        $this->setExpectedException($exception, $message);
        $transformer = new AttributeTransformer($this->managerRegistry, $this->typeRegistry, new Attribute());
        $transformer->transform($value);
    }

    /**
     * @return array
     */
    public function transformExceptionDataProvider()
    {
        $unknownTypeAttribute = new Attribute();
        $unknownTypeAttribute->setType('unknown');

        return [
            'not an attribute' => [
                'Symfony\Component\Form\Exception\UnexpectedTypeException',
                'Expected argument of type "Attribute", "DateTime" given',
                new \DateTime(),
            ],
            'no attribute type' => [
                'Symfony\Component\Form\Exception\TransformationFailedException',
                'Attribute type is not defined',
                new Attribute(),
            ],
            'unknown attribute type' => [
                'Symfony\Component\Form\Exception\TransformationFailedException',
                'Unknown attribute type "unknown"',
                $unknownTypeAttribute,
            ],
        ];
    }

    /**
     * @param string $exception
     * @param string $message
     * @param mixed $value
     * @param Attribute $attribute
     * @dataProvider reverseTransformExceptionDataProvider
     */
    public function testReverseTransformException($exception, $message, $value, Attribute $attribute = null)
    {
        if (!$attribute) {
            $attribute = new Attribute();
        }

        $this->setExpectedException($exception, $message);
        $transformer = new AttributeTransformer($this->managerRegistry, $this->typeRegistry, $attribute);
        $transformer->reverseTransform($value);
    }

    public function reverseTransformExceptionDataProvider()
    {
        $unknownTypeAttribute = new Attribute();
        $unknownTypeAttribute->setType('unknown');

        return [
            'not an array' => [
                'Symfony\Component\Form\Exception\UnexpectedTypeException',
                'Expected argument of type "array", "DateTime" given',
                new \DateTime(),
            ],
            'no attribute type' => [
                'Symfony\Component\Form\Exception\TransformationFailedException',
                'Attribute type is not defined',
                ['code' => 'test'],
            ],
            'unknown attribute type' => [
                'Symfony\Component\Form\Exception\TransformationFailedException',
                'Unknown attribute type "unknown"',
                ['code' => 'test'],
                $unknownTypeAttribute,
            ],
        ];
    }

    /**
     * @param int $id
     * @return Locale
     */
    protected function createLocale($id)
    {
        $locale = new Locale();

        $reflection = new \ReflectionProperty(get_class($locale), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($locale, $id);

        return $locale;
    }

    /**
     * @param int $id
     * @return Locale
     */
    protected function getLocale($id)
    {
        if (empty($this->locales[$id])) {
            $this->locales[$id] = $this->createLocale($id);
        }

        return $this->locales[$id];
    }

    /**
     * @param int $id
     * @return Website
     */
    protected function createWebsite($id)
    {
        $locale = new Website();

        $reflection = new \ReflectionProperty(get_class($locale), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($locale, $id);

        return $locale;
    }

    /**
     * @param int $id
     * @return Website
     */
    protected function getWebsite($id)
    {
        if (empty($this->websites[$id])) {
            $this->websites[$id] = $this->createWebsite($id);
        }

        return $this->websites[$id];
    }

    /**
     * @param int|null $localeId
     * @param string $value
     * @param string|null $fallback
     * @return AttributeLabel
     */
    protected function createLabel($localeId, $value, $fallback = null)
    {
        $locale = null;
        if ($localeId) {
            $locale = $this->getLocale($localeId);
        }

        $label = new AttributeLabel();
        $label->setLocale($locale)
            ->setValue($value)
            ->setFallback($fallback);

        return $label;
    }

    /**
     * @param int|null $localeId
     * @param string|null $fallback
     * @return AttributeDefaultValue
     */
    protected function createDefaultValue($localeId, $fallback = null)
    {
        $locale = null;
        if ($localeId) {
            $locale = $this->getLocale($localeId);
        }

        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setLocale($locale)
            ->setFallback($fallback);

        return $defaultValue;
    }

    /**
     * @param int $id
     * @param int $localeId
     * @param string|null $fallback
     * @return AttributeOption
     */
    protected function createOption($id, $localeId, $fallback = null)
    {
        $locale = null;
        if ($localeId) {
            $locale = $this->getLocale($localeId);
        }

        $option = new AttributeOption();

        $reflection = new \ReflectionProperty(get_class($option), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($option, $id);

        $option->setLocale($locale)
            ->setFallback($fallback);

        return $option;
    }

    /**
     * @param int|null $websiteId
     * @param string $field
     * @param bool $value
     * @param string|null $fallback
     * @return AttributeProperty
     */
    protected function createProperty($websiteId, $field, $value, $fallback = null)
    {
        $website = null;
        if ($websiteId) {
            $website = $this->getWebsite($websiteId);
        }

        $property = new AttributeProperty();
        $property->setWebsite($website)
            ->setField($field)
            ->setValue($value)
            ->setFallback($fallback);

        return $property;
    }

    /**
     * @return Attribute
     */
    protected function getFullAttribute()
    {
        $fullAttribute = new Attribute();
        $fullAttribute->setCode('full')
            ->setType(Integer::NAME)
            ->setLocalized(true)
            ->setSharingType(SharingType::GENERAL)
            ->setRequired(true)
            ->setUnique(true)
            ->setValidation(GreaterThanZero::ALIAS)
            ->addLabel($this->createLabel(null, 'default'))
            ->addLabel($this->createLabel(1, 'first'))
            ->addLabel($this->createLabel(2, null, FallbackType::SYSTEM))
            ->addDefaultValue($this->createDefaultValue(null, null)->setInteger(1))
            ->addDefaultValue($this->createDefaultValue(1, null)->setInteger(2))
            ->addDefaultValue($this->createDefaultValue(2, FallbackType::PARENT_LOCALE));

        $this->addTestProperties($fullAttribute);

        return $fullAttribute;
    }

    /**
     * @return Attribute
     */
    protected function getOptionAttribute()
    {
        $firstGroupDefaultOption = $this->createOption(1, null)->setOrder(1)->setValue('default_1');
        $firstGroupFirstOption = $this->createOption(2, 1)->setOrder(1)->setFallback(FallbackType::SYSTEM);
        $firstGroupSecondOption = $this->createOption(3, 2)->setOrder(1)->setValue('second_1');
        $firstGroupDefaultOption
            ->addRelatedOption($firstGroupFirstOption)
            ->addRelatedOption($firstGroupSecondOption);

        $secondGroupDefaultOption = $this->createOption(4, null)->setOrder(2)->setValue('default_2');
        $secondGroupFirstOption = $this->createOption(5, 1)->setOrder(2)->setValue('first_2');
        $secondGroupSecondOption = $this->createOption(6, 2)->setOrder(2)->setValue('second_2');
        $secondGroupDefaultOption->addRelatedOption($secondGroupFirstOption)
            ->addRelatedOption($secondGroupSecondOption);

        $thirdGroupDefaultOption = $this->createOption(null, null)->setOrder(2)->setValue('default_3');
        $thirdGroupFirstOption = $this->createOption(null, 1)->setOrder(2)->setValue('first_3');
        $thirdGroupSecondOption = $this->createOption(null, 2)->setOrder(2)->setFallback(FallbackType::PARENT_LOCALE);
        $thirdGroupDefaultOption->addRelatedOption($thirdGroupFirstOption)
            ->addRelatedOption($thirdGroupSecondOption);

        $optionAttribute = new Attribute();
        $optionAttribute->setCode('select')
            ->setType(Select::NAME)
            ->resetOptions([
                $firstGroupDefaultOption,
                $firstGroupFirstOption,
                $firstGroupSecondOption,
                $secondGroupDefaultOption,
                $secondGroupFirstOption,
                $secondGroupSecondOption,
                $thirdGroupDefaultOption,
                $thirdGroupFirstOption,
                $thirdGroupSecondOption,
            ])
            ->addDefaultValue($this->createDefaultValue(null)->setOption($firstGroupDefaultOption))
            ->addDefaultValue($this->createDefaultValue(2)->setOption($firstGroupSecondOption))
            ->addDefaultValue($this->createDefaultValue(1)->setOption($secondGroupFirstOption));

        $this->addTestProperties($optionAttribute);

        return $optionAttribute;
    }

    /**
     * @param Attribute $attribute
     */
    protected function addTestProperties(Attribute $attribute)
    {
        $attribute
            ->addProperty($this->createProperty(null, AttributeProperty::FIELD_ON_PRODUCT_VIEW, false))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_ON_PRODUCT_VIEW, true))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_ON_PRODUCT_VIEW, null, FallbackType::SYSTEM)
            )->addProperty($this->createProperty(null, AttributeProperty::FIELD_IN_PRODUCT_LISTING, true))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_IN_PRODUCT_LISTING, false))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_IN_PRODUCT_LISTING, null, FallbackType::SYSTEM)
            )->addProperty($this->createProperty(null, AttributeProperty::FIELD_USE_IN_SORTING, false))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_USE_IN_SORTING, true))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_USE_IN_SORTING, null, FallbackType::SYSTEM)
            )->addProperty($this->createProperty(null, AttributeProperty::FIELD_ON_ADVANCED_SEARCH, true))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_ON_ADVANCED_SEARCH, false))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_ON_ADVANCED_SEARCH, null, FallbackType::SYSTEM)
            )->addProperty($this->createProperty(null, AttributeProperty::FIELD_ON_PRODUCT_COMPARISON, true))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_ON_PRODUCT_COMPARISON, false))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_ON_PRODUCT_COMPARISON, null, FallbackType::SYSTEM)
            )->addProperty($this->createProperty(null, AttributeProperty::FIELD_USE_IN_FILTERS, false))
            ->addProperty($this->createProperty(1, AttributeProperty::FIELD_USE_IN_FILTERS, true))
            ->addProperty(
                $this->createProperty(2, AttributeProperty::FIELD_USE_IN_FILTERS, null, FallbackType::SYSTEM)
            );
    }

    /**
     * @return array
     */
    protected function getFullView()
    {
        return [
            'code' => 'full',
            'type' => Integer::NAME,
            'localized' => true,
            'containHtml' => false,
            'sharingType' => SharingType::GENERAL,
            'required' => true,
            'unique' => true,
            'validation' => GreaterThanZero::ALIAS,
            'label' => [
                null => 'default',
                1 => 'first',
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'defaultValue' => [
                null => 1,
                1 => 2,
                2 => new FallbackType(FallbackType::PARENT_LOCALE),
            ],
            'onProductView' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'inProductListing' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'useInSorting' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'onAdvancedSearch' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'onProductComparison' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'useInFilters' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getOptionView()
    {
        return [
            'code' => 'select',
            'type' => Select::NAME,
            'localized' => false,
            'containHtml' => false,
            'sharingType' => null,
            'required' => false,
            'unique' => false,
            'validation' => null,
            'label' => [],
            'defaultOptions' => [
                [
                    'master_option_id' => 1,
                    'order' => 1,
                    'data' => [
                        null => ['value' => 'default_1', 'is_default' => true],
                        1    => ['value' => new FallbackType(FallbackType::SYSTEM), 'is_default' => false],
                        2    => ['value' => 'second_1', 'is_default' => true],
                    ]
                ],
                [
                    'master_option_id' => null,
                    'order' => 2,
                    'data' => [
                        null => ['value' => 'default_3', 'is_default' => false],
                        1    => ['value' => 'first_3', 'is_default' => false],
                        2    => [
                            'value' => new FallbackType(FallbackType::PARENT_LOCALE),
                            'is_default' => false
                        ],
                    ]
                ],
                [
                    'master_option_id' => 4,
                    'order' => 2,
                    'data' => [
                        null => ['value' => 'default_2', 'is_default' => false],
                        1    => ['value' => 'first_2', 'is_default' => true],
                        2    => ['value' => 'second_2', 'is_default' => false],
                    ]
                ],
            ],
            'onProductView' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'inProductListing' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'useInSorting' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'onAdvancedSearch' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'onProductComparison' => [
                null => true,
                1 => false,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
            'useInFilters' => [
                null => false,
                1 => true,
                2 => new FallbackType(FallbackType::SYSTEM),
            ],
        ];
    }
}
