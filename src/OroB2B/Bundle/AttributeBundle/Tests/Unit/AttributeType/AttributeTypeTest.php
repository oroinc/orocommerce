<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\AttributeType;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\Boolean;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\Float;
use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\AttributeType\Date;
use OroB2B\Bundle\AttributeBundle\AttributeType\DateTime;
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
     * @dataProvider attributeTypeDataProvider
     * @param AttributeTypeInterface $type
     * @param array $expected
     */
    public function testAttributeTypes(AttributeTypeInterface $type, array $expected)
    {
        $this->assertEquals($expected['name'], $type->getName());
        $this->assertEquals($expected['typeField'], $type->getDataTypeField());
        $this->assertEquals($expected['isContainHtml'], $type->isContainHtml());
        $this->assertEquals($expected['isUsedForSearch'], $type->isUsedForSearch());
        $this->assertEquals($expected['isUsedInFilters'], $type->isUsedInFilters());
        $this->assertEquals($expected['formParameters'], $type->getFormParameters());
        $this->assertEquals($expected['requiredConstraints'], $type->getRequiredConstraints());
        $this->assertEquals($expected['optionalConstraints'], $type->getOptionalConstraints());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function attributeTypeDataProvider()
    {
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
                        'type'  => 'integer'
                    ],
                    'requiredConstraints' => [
                        new IntegerConstraint()
                    ],
                    'optionalConstraints' => [
                        new GreaterThanZero(),
                        new Decimal()
                    ]
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
                    'optionalConstraints' => []
                ]
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
                        new Decimal(),
                        new Integer()
                    ]
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
                    ]
                ]
            ],
            'text' => [
                'attributeType' => new Text(),
                'expected' => [
                    'name' => Text::NAME,
                    'typeField' => 'text',
                    'isContainHtml' => true,
                    'isUsedForSearch' => true,
                    'isUsedInFilters' => false,
                    'formParameters' => [
                        'type'  => 'textarea'
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
                    ]
                ]
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
                    'optionalConstraints' => []
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
                    'optionalConstraints' => []
                ]
            ]
        ];
    }
}
