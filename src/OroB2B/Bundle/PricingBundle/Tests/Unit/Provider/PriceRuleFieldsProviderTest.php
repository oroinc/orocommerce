<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceRuleAttributeProvider = new PriceRuleFieldsProvider(
            $this->entityFieldProvider,
            $this->doctrineHelper
        );
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
}
