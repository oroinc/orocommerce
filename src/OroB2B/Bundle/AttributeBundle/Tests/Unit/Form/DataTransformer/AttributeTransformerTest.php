<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeDefaultValue;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeLabel;
use OroB2B\Bundle\AttributeBundle\Entity\AttributeProperty;
use OroB2B\Bundle\AttributeBundle\Form\DataTransformer\AttributeTransformer;
use OroB2B\Bundle\AttributeBundle\Model\FallbackType;
use OroB2B\Bundle\AttributeBundle\Model\SharingType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;

class AttributeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Locale[]
     */
    protected static $locales = [];

    /**
     * @var Website[]
     */
    protected static $websites = [];

    protected $managerRegistry;

    protected $typeRegistry;

    protected function setUp()
    {
        $localeRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $localeRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap([[1, self::getLocale(1)], [2, self::getLocale(2)]]));

        $websiteRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $websiteRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValueMap([[1, self::getWebsite(1)], [2, self::getWebsite(2)]]));

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
                'view' => [
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
                    'useForSearch' => [
                        null => false,
                    ],
                    'useInFilters' => [
                        null => false,
                        1 => true,
                        2 => new FallbackType(FallbackType::SYSTEM),
                    ],
                ],
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
        $this->assertEquals($model, $transformer->reverseTransform($view));
    }

    /**
     * @return array
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

        return [
            'null' => [
                'view'  => null,
                'model' => null,
            ],
            'full localized attribute' => [
                'view'  => [
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
                ],
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
    protected static function createLocale($id)
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
    protected static function getLocale($id)
    {
        if (empty(self::$locales[$id])) {
            self::$locales[$id] = self::createLocale($id);
        }

        return self::$locales[$id];
    }

    /**
     * @param int $id
     * @return Website
     */
    protected static function createWebsite($id)
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
    protected static function getWebsite($id)
    {
        if (empty(self::$websites[$id])) {
            self::$websites[$id] = self::createWebsite($id);
        }

        return self::$websites[$id];
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
            $locale = self::getLocale($localeId);
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
            $locale = self::getLocale($localeId);
        }

        $defaultValue = new AttributeDefaultValue();
        $defaultValue->setLocale($locale)
            ->setFallback($fallback);

        return $defaultValue;
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
            $website = self::getWebsite($websiteId);
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
            ->addDefaultValue($this->createDefaultValue(2, FallbackType::PARENT_LOCALE))
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

        return $fullAttribute;
    }
}
