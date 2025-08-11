<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Api\Filter\SearchFieldResolver;
use Oro\Bundle\WebsiteSearchBundle\Api\Filter\SearchFieldResolverFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchFieldResolverFactoryTest extends TestCase
{
    private AbstractSearchMappingProvider&MockObject $searchMappingProvider;
    private SearchFieldResolverFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);

        $this->factory = new SearchFieldResolverFactory($this->searchMappingProvider);
    }

    public function testCreateFilter(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['apiField1' => 'field1'];
        $mapping = [
            'fields' => [
                ['name' => 'field1', 'type' => 'integer'],
                ['name' => 'field2', 'type' => 'datetime'],
                ['name' => 'field3', 'type' => 'text']
            ]
        ];

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $expectedFieldResolver = new SearchFieldResolver(
            [
                'field1' => ['type' => 'integer'],
                'field2' => ['type' => 'datetime'],
                'field3' => ['type' => 'text'],
                'all_text' => ['type' => 'text']
            ],
            $fieldMappings,
            true
        );

        self::assertEquals(
            $expectedFieldResolver,
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }

    public function testCreateFilterWhenNoTextFields(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['apiField1' => 'field1'];
        $mapping = [
            'fields' => [
                ['name' => 'field1', 'type' => 'integer'],
                ['name' => 'field2', 'type' => 'datetime']
            ]
        ];

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $expectedFieldResolver = new SearchFieldResolver(
            [
                'field1' => ['type' => 'integer'],
                'field2' => ['type' => 'datetime']
            ],
            $fieldMappings,
            true
        );

        self::assertEquals(
            $expectedFieldResolver,
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }

    public function testCreateFilterForUnknownEntity(): void
    {
        $entityClass = 'Test\Entity';
        $fieldMappings = ['apiField1' => 'field1'];

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn([]);

        $expectedFieldResolver = new SearchFieldResolver([], $fieldMappings, true);

        self::assertEquals(
            $expectedFieldResolver,
            $this->factory->createFieldResolver($entityClass, $fieldMappings)
        );
    }
}
