<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Provider\ChainVirtualFieldProvider;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;

class PriceRuleAttributeProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ChainVirtualFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $virtualFieldProvider;

    /**
     * @var PriceRuleAttributeProvider
     */
    protected $priceRuleAttributeProvider;

    /**
     * @var ClassMetadata
     */
    protected $metadata;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();


        $this->virtualFieldProvider = $this->getMock(ChainVirtualFieldProvider::class);
        $this->priceRuleAttributeProvider = new PriceRuleAttributeProvider(
            $this->registry,
            $this->virtualFieldProvider
        );
    }

    /**
     * @dataProvider ruleAttributeDataProvider
     * @param array $fields
     * @param array $virtualField
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetAvailableRuleAttributes(array $fields, array $virtualField, array $expectedFields)
    {

        $this->mockManager($fields);
        $className = 'ClassName';
        $this->virtualFieldProvider->method('getVirtualFields')->willReturn($virtualField);

        $this->priceRuleAttributeProvider->addSupportedClass($className);
        $actualFields = $this->priceRuleAttributeProvider->getAvailableRuleAttributes($className);

        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function ruleAttributeDataProvider()
    {
        return [
            [
                'fields' => [
                    ['field1', 'integer'],
                    ['field2', 'money'],
                    ['field3', 'string'],
                    ['field4', 'float'],
                ],
                'virtualField' => ['virtualField'],
                'expectedFields' => [
                    'field1' => [
                        'name' => 'field1',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'integer',
                    ],
                    'field2' => [
                        'name' => 'field2',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'money',
                    ],
                    'field4' => [
                        'name' => 'field4',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'float',
                    ],
                ]
            ]
        ];
    }

    /**
     * @dataProvider conditionalAttributeDataProvider
     * @param array $fields
     * @param array $virtualField
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetAvailableConditionAttributes(array $fields, array $virtualField, array $expectedFields)
    {
        $this->mockManager($fields);
        $className = 'ClassName';
        $this->virtualFieldProvider->method('getVirtualFields')->willReturn($virtualField);

        $this->priceRuleAttributeProvider->addSupportedClass($className);
        $actualFields = $this->priceRuleAttributeProvider->getAvailableConditionAttributes($className);
        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function conditionalAttributeDataProvider()
    {
        return [
            [
                'fields' => [
                    ['field1', 'integer'],
                    ['field2', 'money'],
                    ['field3', 'string'],
                    ['field4', 'float'],
                ],
                'virtualField' => ['virtualField'],
                'expectedFields' => [
                    'field1' => [
                        'name' => 'field1',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'integer',
                    ],
                    'field2' => [
                        'name' => 'field2',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'money',
                    ],
                    'field3' => [
                        'name' => 'field3',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'string',
                    ],
                    'field4' => [
                        'name' => 'field4',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_NATIVE,
                        'data_type' => 'float',
                    ],
                    'virtualField' => [
                        'name' => 'virtualField',
                        'type' => PriceRuleAttributeProvider::FIELD_TYPE_VIRTUAL,
                        'data_type' => null,
                    ],
                ]
            ]
        ];
    }

    public function testAddAvailableClass()
    {
        $class = 'ClassName';
        $this->priceRuleAttributeProvider->addSupportedClass($class);
        $this->assertEquals(['ClassName'], $this->priceRuleAttributeProvider->getSupportedClasses());
        $this->assertTrue($this->priceRuleAttributeProvider->isClassSupported($class));
        $this->assertFalse($this->priceRuleAttributeProvider->isClassSupported('invalidClassName'));
    }

    /**
     * @param array $fields
     */
    protected function mockManager(array $fields)
    {
        $fieldsNames = array_map(function ($field) {
            return $field[0];
        }, $fields);
        $metadata = $this->getMock(ClassMetadata::class);
        $metadata->expects($this->once())->method('getFieldNames')
            ->willReturn($fieldsNames);
        $metadata->method('getTypeOfField')->willReturnMap($fields);

        $manager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $manager->method('getClassMetadata')->willReturn($metadata);
        $this->registry->method('getManagerForClass')->willReturn($manager);
    }
    
    /**
     * @expectedException \Exception
     */
    public function testGetAvailableConditionAttributesException()
    {
        $this->priceRuleAttributeProvider->getAvailableConditionAttributes('ClassName');
    }

    /**
     * @expectedException \Exception
     */
    public function testGetAvailableRuleAttributesException()
    {
        $this->priceRuleAttributeProvider->getAvailableRuleAttributes('ClassName');
    }
}
