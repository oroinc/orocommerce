<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;

class PriceRuleAttributeProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFieldProvider;

    /**
     * @var PriceRuleAttributeProvider
     */
    protected $priceRuleAttributeProvider;


    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceRuleAttributeProvider = new PriceRuleAttributeProvider($this->entityFieldProvider);
    }

    /**
     * @dataProvider ruleAttributeDataProvider
     * @param array $fields
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetAvailableRuleAttributes(array $fields, array $expectedFields)
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

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
                    ['name' => 'field1', 'type' => 'integer'],
                    ['name' => 'field2', 'type' => 'money'],
                    ['name' => 'field3', 'type' => 'string'],
                    ['name' => 'field4', 'type' => 'float'],
                    ['name' => 'virtualField', 'type' => 'string'],
                ],
                'expectedFields' => [
                    'field1',
                    'field2',
                    'field4',
                ]
            ]
        ];
    }

    /**
     * @dataProvider conditionalAttributeDataProvider
     * @param array $fields
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetAvailableConditionAttributes(array $fields, array $expectedFields)
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

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
                    ['name' => 'field1', 'type' => 'integer'],
                    ['name' => 'field2', 'type' => 'money'],
                    ['name' => 'field3', 'type' => 'string'],
                    ['name' => 'field4', 'type' => 'float'],
                    ['name' => 'virtualField', 'type' => 'string'],
                ],
                'expectedFields' => [
                    'field1',
                    'field2',
                    'field3',
                    'field4',
                    'virtualField',
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
