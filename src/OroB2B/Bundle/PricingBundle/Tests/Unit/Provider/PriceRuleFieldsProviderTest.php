<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class PriceRuleFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFieldProvider;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $priceRuleAttributeProvider;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock(RegistryInterface::class);

        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceRuleAttributeProvider = new PriceRuleFieldsProvider($this->entityFieldProvider);
    }

    /**
     * @dataProvider ruleFieldsDataProvider
     * @param array $fields
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testFieldsForRule(array $fields, array $expectedFields)
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

        $this->priceRuleAttributeProvider->addSupportedClass($className);
        $actualFields = $this->priceRuleAttributeProvider->getFields($className, true);

        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function ruleFieldsDataProvider()
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
     * @dataProvider conditionalFieldsDataProvider
     * @param array $fields
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testFieldsForCondition(array $fields, array $expectedFields)
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

        $this->priceRuleAttributeProvider->addSupportedClass($className);
        $actualFields = $this->priceRuleAttributeProvider->getFields($className, false, true);
        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function conditionalFieldsDataProvider()
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
        $this->assertTrue($this->priceRuleAttributeProvider->isClassSupported($class));
        $this->assertFalse($this->priceRuleAttributeProvider->isClassSupported('invalidClassName'));
    }

    /**
     * @expectedException \Exception
     */
    public function testGetAvailableRuleAttributesException()
    {
        $this->priceRuleAttributeProvider->getFields('ClassName');
    }
}
