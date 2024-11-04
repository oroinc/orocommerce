<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\ProductBundle\Filter\ComposedSkuStringFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ComposedSkuStringFilterTest extends TestCase
{
    private ComposedSkuStringFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $this->filter = new ComposedSkuStringFilter($formFactory, new FilterUtility());
        $this->filter->init(
            'test-filter',
            [
                FilterUtility::DATA_NAME_KEY => 'field_name',
            ]
        );
    }

    /**
     * @dataProvider getApplyDataProvider
     */
    public function testApply(array $data, array $expected): void
    {
        $ds = $this->getFilterDatasource();

        $this->filter->apply($ds, $data);

        self::assertEquals($expected['where'], $this->parseQueryCondition($ds));
    }

    public function getApplyDataProvider(): array
    {
        return [
            'no type' => [
                'data' => [
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'contains' => [
                'data' => [
                    'type' => TextFilterType::TYPE_CONTAINS,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'not contains' => [
                'data' => [
                    'type' => TextFilterType::TYPE_NOT_CONTAINS,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) NOT LIKE LOWER(%test%)',
                ],
            ],
            'is equal' => [
                'data' => [
                    'type' => TextFilterType::TYPE_EQUAL,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'starts with' => [
                'data' => [
                    'type' => TextFilterType::TYPE_STARTS_WITH,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'ends with' => [
                'data' => [
                    'type' => TextFilterType::TYPE_ENDS_WITH,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'is any of' => [
                'data' => [
                    'type' => TextFilterType::TYPE_IN,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test1%) OR LOWER(field_name) LIKE LOWER(%test2%)',
                ],
            ],
            'is not any of' => [
                'data' => [
                    'type' => TextFilterType::TYPE_NOT_IN,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) NOT LIKE LOWER(%test1%)'
                        . ' AND LOWER(field_name) NOT LIKE LOWER(%test2%)',
                ],
            ],
            'is empty' => [
                'data' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'field_name IS NULL OR LOWER(field_name) = LOWER(\'\')',
                ],
            ],
            'not empty' => [
                'data' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'field_name IS NOT NULL AND LOWER(field_name) <> LOWER(\'\')',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getApplyWithCustomSeparatorDataProvider
     */
    public function testApplyWithCustomSeparator(array $data, array $expected): void
    {
        $ds = $this->getFilterDatasource();

        $this->filter->setSeparator('|');
        $this->filter->apply($ds, $data);

        self::assertEquals($expected['where'], $this->parseQueryCondition($ds));
    }

    public function getApplyWithCustomSeparatorDataProvider(): array
    {
        return [
            'no type' => [
                'data' => [
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%|test|%)',
                ],
            ],
            'contains' => [
                'data' => [
                    'type' => TextFilterType::TYPE_CONTAINS,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test%)',
                ],
            ],
            'not contains' => [
                'data' => [
                    'type' => TextFilterType::TYPE_NOT_CONTAINS,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) NOT LIKE LOWER(%test%)',
                ],
            ],
            'is equal' => [
                'data' => [
                    'type' => TextFilterType::TYPE_EQUAL,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%|test|%)',
                ],
            ],
            'starts with' => [
                'data' => [
                    'type' => TextFilterType::TYPE_STARTS_WITH,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%|test%)',
                ],
            ],
            'ends with' => [
                'data' => [
                    'type' => TextFilterType::TYPE_ENDS_WITH,
                    'value' => 'test',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%test|%)',
                ],
            ],
            'is any of' => [
                'data' => [
                    'type' => TextFilterType::TYPE_IN,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) LIKE LOWER(%|test1|%) OR LOWER(field_name) LIKE LOWER(%|test2|%)',
                ],
            ],
            'is not any of' => [
                'data' => [
                    'type' => TextFilterType::TYPE_NOT_IN,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'LOWER(field_name) NOT LIKE LOWER(%|test1|%)'
                        . ' AND LOWER(field_name) NOT LIKE LOWER(%|test2|%)',
                ],
            ],
            'is empty' => [
                'data' => [
                    'type' => FilterUtility::TYPE_EMPTY,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'field_name IS NULL OR LOWER(field_name) = LOWER(\'\')',
                ],
            ],
            'not empty' => [
                'data' => [
                    'type' => FilterUtility::TYPE_NOT_EMPTY,
                    'value' => 'test1, test2',
                ],
                'expected' => [
                    'where' => 'field_name IS NOT NULL AND LOWER(field_name) <> LOWER(\'\')',
                ],
            ],
        ];
    }

    private function getFilterDatasource(): OrmFilterDatasourceAdapter
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSQL94Platform());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());
        $entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);

        return new OrmFilterDatasourceAdapter(new QueryBuilder($entityManager));
    }

    private function parseQueryCondition(OrmFilterDatasourceAdapter $ds): string
    {
        $qb = $ds->getQueryBuilder();

        $parameters = [];
        foreach ($qb->getParameters() as $param) {
            /* @var Query\Parameter $param */
            $parameters[':' . $param->getName()] = $param->getValue();
        }

        $parts = $qb->getDQLParts();
        if (!$parts['where']) {
            return '';
        }

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            (string)$parts['where']
        );
    }
}
