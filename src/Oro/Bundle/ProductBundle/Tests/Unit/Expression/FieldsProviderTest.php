<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Expression\FieldsProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFieldProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FieldsProvider */
    private $fieldsProvider;

    protected function setUp(): void
    {
        $this->entityFieldProvider = $this->createMock(EntityFieldProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->fieldsProvider = new FieldsProvider(
            $this->entityFieldProvider,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider ruleFieldsDataProvider
     */
    public function testFieldsForRule(array $fields, array $expectedFields): void
    {
        $className = 'ClassName';
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, true);

        self::assertEquals($expectedFields, $actualFields);
    }

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
     */
    public function testFieldsForCondition(array $fields, array $expectedFields): void
    {
        $className = 'ClassName';
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);

        $actualFields = $this->fieldsProvider->getFields($className, false, true);
        self::assertEquals($expectedFields, $actualFields);
    }

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
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);

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
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);

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
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);

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
     */
    public function testGetRealClassName(string $className, ?string $field, string $expectedClassName): void
    {
        $fields = [
            [
                'name' => 'field',
                'type' => 'ref-one',
                'relation_type' => 'ref-one',
                'related_entity_name' => 'DateTime'
            ]
        ];
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn($fields);
        self::assertEquals($expectedClassName, $this->fieldsProvider->getRealClassName($className, $field));
    }

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
        $this->entityFieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->willReturn([]);
        $this->fieldsProvider->getRealClassName('stdClass::field');
    }

    public function testIsRelation(): void
    {
        $this->entityFieldProvider->expects(self::any())
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
        $this->entityFieldProvider->expects(self::any())
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
        $this->entityFieldProvider->expects(self::any())
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
