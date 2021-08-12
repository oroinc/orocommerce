<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Expression\FieldsProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    private EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject $entityFieldProvider;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private FieldsProvider $fieldsProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldsProvider = new FieldsProvider(
            $this->entityFieldProvider,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider ruleFieldsDataProvider
     * @throws \Exception
     */
    public function testFieldsForRule(array $fields, array $expectedFields): void
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, true);

        self::assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function ruleFieldsDataProvider(): array
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
     * @throws \Exception
     */
    public function testFieldsForCondition(array $fields, array $expectedFields): void
    {
        $className = 'ClassName';
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, false, true);
        self::assertEquals($expectedFields, $actualFields);
    }

    /**
     * @return array
     */
    public function conditionalFieldsDataProvider(): array
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

    public function testFieldsWhiteList(): void
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
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);

        $this->fieldsProvider->addFieldToWhiteList($className, 'field3');
        $actualFields = $this->fieldsProvider->getFields($className, true);
        self::assertEquals($expectedFields, $actualFields);
    }

    public function testFieldsBlackList(): void
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
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);

        $this->fieldsProvider->addFieldToBlackList($className, 'field2');
        $actualFields = $this->fieldsProvider->getFields($className, true);
        self::assertEquals($expectedFields, $actualFields);
    }

    public function testRelations(): void
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
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, false, true);
        self::assertEquals($expectedFields, $actualFields);
    }

    public function testGetRealClassNameClassOnly(): void
    {
        $className = 'stdClass';
        self::assertEquals('stdClass', $this->fieldsProvider->getRealClassName($className));
    }

    /**
     * @dataProvider classNameDataProvider
     * @param string $className
     * @param string $field
     * @param string $expectedClassName
     */
    public function testGetRealClassName($className, $field, $expectedClassName): void
    {
        $fields = [
            [
                'name' => 'field',
                'type' => 'ref-one',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'DateTime'
            ]
        ];
        $this->entityFieldProvider->method('getEntityFields')->willReturn($fields);
        self::assertEquals($expectedClassName, $this->fieldsProvider->getRealClassName($className, $field));
    }

    /**
     * @return array
     */
    public function classNameDataProvider(): array
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

    public function testGetRealClassNameException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "field" is not found in class stdClass');
        $this->entityFieldProvider->method('getEntityFields')->willReturn([]);
        $this->fieldsProvider->getRealClassName('stdClass::field');
    }

    public function testIsRelation(): void
    {
        $this->entityFieldProvider
            ->method('getEntityFields')
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
        self::assertTrue($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testIsRelationNoField(): void
    {
        $this->entityFieldProvider
            ->method('getEntityFields')
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
        self::assertFalse($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testIsRelationNotRelation(): void
    {
        $this->entityFieldProvider
            ->method('getEntityFields')
            ->with('stdClass')
            ->willReturn(
                [
                    [
                        'name' => 'field',
                        'type' => 'string'
                    ]
                ]
            );
        self::assertFalse($this->fieldsProvider->isRelation('stdClass', 'field'));
    }

    public function testGetIdentityFieldName(): void
    {
        $className = 'stdClass';
        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($className, false)
            ->willReturn('id');

        self::assertEquals('id', $this->fieldsProvider->getIdentityFieldName('stdClass'));
    }
}
