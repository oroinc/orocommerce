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

class AttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider attributeTypeDataProvider
     * @param AttributeTypeInterface $type
     * @param array $options
     * @param array $expected
     */
    public function testAttributeTypes(AttributeTypeInterface $type, array $expected, array $options = null)
    {
        $this->assertEquals($expected['name'], $type->getName());
        $this->assertEquals($expected['typeField'], $type->getDataTypeField());
        $this->assertEquals($expected['isContainHtml'], $type->isContainHtml());
        $this->assertEquals($expected['isUsedForSearch'], $type->isUsedForSearch());
        $this->assertEquals($expected['isUsedInFilters'], $type->isUsedInFilters());
        $this->assertEquals($expected['formParameters'], $type->getFormParameters($options));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function attributeTypeDataProvider()
    {
        return [
            'integer without options' => [
                'attributeType' => new Integer(),
                'expected' => [
                    'name' => Integer::NAME,
                    'typeField' => 'integer',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type'  => 'integer'
                    ]
                ]
            ],
            'integer with options' => [
                'attributeType' => new Integer(),
                'expected' => [
                    'name' => Integer::NAME,
                    'typeField' => 'integer',
                    'isContainHtml' => false,
                    'isUsedForSearch' => false,
                    'isUsedInFilters' => true,
                    'formParameters' => [
                        'type'  => 'integer'
                    ]
                ],
                'options' => [
                    'data' => 0,
                    'precision' => 0
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
                    ]
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
                        'type'  => 'text'
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
                    ]
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
                    ]
                ]
            ]
        ];
    }
}
