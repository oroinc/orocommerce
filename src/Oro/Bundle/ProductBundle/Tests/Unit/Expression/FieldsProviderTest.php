<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Expression\FieldsProvider;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    public function testFieldsWhiteList()
    {
        $fields = [
            ['name' => 'field1', 'type' => 'integer'],
            ['name' => 'field2', 'type' => 'money'],
            ['name' => 'field3', 'type' => 'string'],
            ['name' => 'field4', 'type' => 'float'],
            ['name' => 'virtualField', 'type' => 'string']
        ];
        $expectedFields = [
            'field1',
            'field2',
            'field3',
            'field4'
        ];

        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

        $this->fieldsProvider->addFieldToWhiteList($className, 'field3');
        $actualFields = $this->fieldsProvider->getFields($className, true);
        $this->assertEquals($expectedFields, $actualFields);
    }

    public function testFieldsBlackList()
    {
        $fields = [
            ['name' => 'field1', 'type' => 'integer'],
            ['name' => 'field2', 'type' => 'money'],
            ['name' => 'field3', 'type' => 'string'],
            ['name' => 'field4', 'type' => 'float'],
            ['name' => 'virtualField', 'type' => 'string']
        ];
        $expectedFields = [
            'field1',
            'field4'
        ];

        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

        $this->fieldsProvider->addFieldToBlackList($className, 'field2');
        $actualFields = $this->fieldsProvider->getFields($className, true);
        $this->assertEquals($expectedFields, $actualFields);
    }

    public function testRelations()
    {
        $fields = [
            [
                'name' => 'field1',
                'type' => 'ref-one',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'RelatedEntity'
            ],
            [
                'name' => 'field2',
                'type' => 'ref-many',
                'relation_type' => 'ref-many',
                'related_entity_name' => 'RelatedEntity'
            ]
        ];
        $expectedFields = [
            'field1'
        ];

        $className = 'ClassName';
        $this->entityFieldProvider->method('getFields')->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, false, true);
        $this->assertEquals($expectedFields, $actualFields);
    }

    public function testGetRealClassNameClassOnly()
    {
        $className = 'stdClass';
        $this->assertEquals('stdClass', $this->fieldsProvider->getRealClassName($className));
    }

    /**
     * @dataProvider classNameDataProvider
     * @param string $className
     * @param string $field
     * @param string $expectedClassName
     */
    public function testGetRealClassName($className, $field, $expectedClassName)
    {
        $fields = [
            [
                'name' => 'field',
                'type' => 'ref-one',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'DateTime'
            ]
        ];
        $this->entityFieldProvider->method('getFields')->willReturn($fields);
        $this->assertEquals($expectedClassName, $this->fieldsProvider->getRealClassName($className, $field));
    }

    /**
     * @return array
     */
    public function classNameDataProvider()
    {
        return [
            ':: notation' => [
                'stdClass::field',
                null,
                'DateTime'
            ],
            'both args' => [
                'stdClass',
                'field',
                'DateTime'
            ]
        ];
    }

    public function testGetRealClassNameException()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'Field "field" is not found in class stdClass');
        $this->entityFieldProvider->method('getFields')->willReturn([]);
        $this->fieldsProvider->getRealClassName('stdClass::field');
    }

    public function testIsRelation()
    {
        $this->entityFieldProvider
            ->method('getFields')
            ->with('stdClass')
            ->willReturn(
                [
                    [
                        'name' => 'field',
                        'type' => 'ref-one',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'DateTime'
                    ]
                ]
            );
        $this->assertTrue($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testIsRelationNoField()
    {
        $this->entityFieldProvider
            ->method('getFields')
            ->with('stdClass')
            ->willReturn(
                [
                    [
                        'name' => 'field1',
                        'type' => 'ref-one',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'DateTime'
                    ]
                ]
            );
        $this->assertFalse($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testIsRelationNotRelation()
    {
        $this->entityFieldProvider
            ->method('getFields')
            ->with('stdClass')
            ->willReturn(
                [
                    [
                        'name' => 'field',
                        'type' => 'string'
                    ]
                ]
            );
        $this->assertFalse($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testGetIdentityFieldName()
    {
        $className = 'stdClass';
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($className, false)
            ->willReturn('id');

        $this->assertEquals('id', $this->fieldsProvider->getIdentityFieldName('stdClass'));
    }
}
