<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Expression\FieldsProvider;
use Oro\Component\DependencyInjection\ServiceLink;

class FieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFieldProvider;

    /**
     * @var FieldsProvider
     */
    protected $fieldsProvider;

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

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink
         */
        $entityFieldProviderLink = $this
            ->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $entityFieldProviderLink
            ->expects($this->any())
            ->method('getService')
            ->willReturn($this->entityFieldProvider);

        $this->fieldsProvider = new FieldsProvider(
            $entityFieldProviderLink,
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

        $actualFields = $this->fieldsProvider->getFields($className, true);

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

        $actualFields = $this->fieldsProvider->getFields($className, false, true);
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
