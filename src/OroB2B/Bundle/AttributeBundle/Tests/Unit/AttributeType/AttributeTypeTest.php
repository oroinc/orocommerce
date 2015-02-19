<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\AttributeType;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\Boolean;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\Float;
use OroB2B\Bundle\AttributeBundle\AttributeType\MultiSelect;
use OroB2B\Bundle\AttributeBundle\AttributeType\OptionAttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\Select;
use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\AttributeType\Date;
use OroB2B\Bundle\AttributeBundle\AttributeType\DateTime;
use OroB2B\Bundle\AttributeBundle\Entity\Attribute;
use OroB2B\Bundle\AttributeBundle\Form\Type\LocalizedMultiselectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\MultiSelectAttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\NotLocalizedMultiselectCollectionType;
use OroB2B\Bundle\AttributeBundle\Form\Type\SelectAttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Alphanumeric;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Email;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Letters;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Url;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\UrlSafe;

class AttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param AttributeTypeInterface|OptionAttributeTypeInterface $type
     * @param array $expected
     * @param Attribute|null $attribute
     * @param array $normalizationData
     * @dataProvider attributeTypeDataProvider
     */
    public function testAttributeTypes(
        AttributeTypeInterface $type,
        array $expected,
        Attribute $attribute = null,
        array $normalizationData = []
    ) {
        if (!$attribute) {
            $attribute = new Attribute();
        }

        $this->assertEquals($expected['name'], $type->getName());
        $this->assertEquals($expected['typeField'], $type->getDataTypeField());
        $this->assertEquals($expected['isContainHtml'], $type->isContainHtml());
        $this->assertEquals($expected['isUsedForSearch'], $type->isUsedForSearch());
        $this->assertEquals($expected['isUsedInFilters'], $type->isUsedInFilters());
        $this->assertEquals($expected['formParameters'], $type->getFormParameters($attribute));
        $this->assertEquals($expected['requiredConstraints'], $type->getRequiredConstraints());
        $this->assertEquals($expected['optionalConstraints'], $type->getOptionalConstraints());
        $this->assertEquals($expected['canBeUnique'], $type->canBeUnique());
        $this->assertEquals($expected['canBeRequired'], $type->canBeRequired());

        if (isset($expected['defaultFormParameters'])) {
            $this->assertEquals($expected['defaultFormParameters'], $type->getDefaultValueFormParameters($attribute));
        }

        $testValue = 'test';

        if (!empty($normalizationData['normalize'])) {
            foreach ($normalizationData['normalize'] as $value) {
                $this->assertSame($value['to'], $type->normalize($value['from']));
            }
        } else {
            $this->assertSame($testValue, $type->normalize($testValue));
        }

        if (!empty($normalizationData['denormalize'])) {
            foreach ($normalizationData['denormalize'] as $value) {
                $this->assertSame($value['to'], $type->denormalize($value['from']));
            }
        } else {
            $this->assertSame($testValue, $type->denormalize($testValue));
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function attributeTypeDataProvider()
    {
        $htmlAttribute = new Attribute();
        $htmlAttribute->setContainHtml(true);

        $localizedAttribute = new Attribute();
        $localizedAttribute->setLocalized(true);

        $notLocalizedAttribute = new Attribute();
        $notLocalizedAttribute->setLocalized(false);

        return [
            'integer' => [
                'attributeType' => new Integer(),
                'expected' => [
                    'name' => Integer::NAME,
                    'typeField' => 'integer',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type' => 'integer',
                        'options' => ['type' => 'text'],
                    ],
                    'requiredConstraints' => [
                        new IntegerConstraint()
                    ],
                    'optionalConstraints' => [
                        new GreaterThanZero()
                    ],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'boolean' => [
                'attributeType' => new Boolean(),
                'expected' => [
                    'name' => Boolean::NAME,
                    'typeField' => 'integer',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type'  => 'checkbox'
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => false,
                    'canBeRequired' => false,
                ],
                'attribute' => null,
                'normalizationData' => [
                    'normalize' => [
                        ['from' => null, 'to' => null],
                        ['from' => 0, 'to' => false],
                        ['from' => 1, 'to' => true],
                    ],
                   'denormalize' => [
                       ['from' => null, 'to' => null],
                       ['from' => '', 'to' => 0],
                       ['from' => '0', 'to' => 0],
                       ['from' => '1', 'to' => 1],
                   ],
                ],
            ],
            'float' => [
                'attributeType' => new Float(),
                'expected' => [
                    'name' => Float::NAME,
                    'typeField' => 'float',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type'  => 'number'
                    ],
                    'requiredConstraints' => [
                        new Decimal()
                    ],
                    'optionalConstraints' => [
                        new GreaterThanZero(),
                        new IntegerConstraint()
                    ],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'string' => [
                'attributeType' => new String(),
                'expected' => [
                    'name' => String::NAME,
                    'typeField' => 'string',
                    'isContainHtml' => true,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type'  => 'text'
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [
                        new Letters(),
                        new Alphanumeric(),
                        new UrlSafe(),
                        new Decimal(),
                        new IntegerConstraint(),
                        new Email(),
                        new Url()
                    ],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'text not localized' => [
                'attributeType' => new Text(),
                'expected' => [
                    'name' => Text::NAME,
                    'typeField' => 'text',
                    'isContainHtml' => true,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type' => 'textarea',
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [
                        new Letters(),
                        new Alphanumeric(),
                        new UrlSafe(),
                        new Decimal(),
                        new IntegerConstraint(),
                        new Email(),
                        new Url()
                    ],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'text localized' => [
                'attributeType' => new Text(),
                'expected' => [
                    'name' => Text::NAME,
                    'typeField' => 'text',
                    'isContainHtml' => true,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type' => 'oro_rich_text',
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [
                        new Letters(),
                        new Alphanumeric(),
                        new UrlSafe(),
                        new Decimal(),
                        new IntegerConstraint(),
                        new Email(),
                        new Url()
                    ],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ],
                'attribute' => $htmlAttribute
            ],
            'date' => [
                'attributeType' => new Date(),
                'expected' => [
                    'name' => Date::NAME,
                    'typeField' => 'datetime',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type'  => 'oro_date'
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'datetime' => [
                'attributeType' => new DateTime(),
                'expected' => [
                    'name' => DateTime::NAME,
                    'typeField' => 'datetime',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type'  => 'oro_datetime'
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => true,
                    'canBeRequired' => true,
                ]
            ],
            'select not localized' => [
                'attributeType' => new Select(),
                'expected' => [
                    'name' => Select::NAME,
                    'typeField' => 'options',
                    'isContainHtml' => false,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type' => SelectAttributeTypeType::NAME,
                    ],
                    'defaultFormParameters' => [
                        'type' => 'options_not_localized',
                        'options' => [],
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => false,
                    'canBeRequired' => true,
                ],
                'attribute' => $notLocalizedAttribute
            ],
            'select localized' => [
                'attributeType' => new Select(),
                'expected' => [
                    'name' => Select::NAME,
                    'typeField' => 'options',
                    'isContainHtml' => false,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type' => SelectAttributeTypeType::NAME
                    ],
                    'defaultFormParameters' => [
                        'type' => 'options_localized',
                        'options' => [],
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => false,
                    'canBeRequired' => true,
                ],
                'attribute' => $localizedAttribute
            ],
            'multiselect not localized' => [
                'attributeType' => new MultiSelect(),
                'expected' => [
                    'name' => MultiSelect::NAME,
                    'typeField' => 'options',
                    'isContainHtml' => false,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type' => MultiSelectAttributeTypeType::NAME
                    ],
                    'defaultFormParameters' => [
                        'type' => NotLocalizedMultiselectCollectionType::NAME,
                        'options' => [],
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => false,
                    'canBeRequired' => true,
                ],
                'attribute' => $notLocalizedAttribute
            ],
            'multiselect localized' => [
                'attributeType' => new MultiSelect(),
                'expected' => [
                    'name' => MultiSelect::NAME,
                    'typeField' => 'options',
                    'isContainHtml' => false,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type' => MultiSelectAttributeTypeType::NAME
                    ],
                    'defaultFormParameters' => [
                        'type' => LocalizedMultiselectCollectionType::NAME,
                        'options' => [],
                    ],
                    'requiredConstraints' => [],
                    'optionalConstraints' => [],
                    'canBeUnique' => false,
                    'canBeRequired' => true,
                ],
                'attribute' => $localizedAttribute
            ],
        ];
    }
}
