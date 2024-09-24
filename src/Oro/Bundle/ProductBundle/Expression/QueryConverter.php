<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Converts a price list query definition created by the query designer to an ORM query.
 */
class QueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    private array $tableAliasByColumn = [];

    /** @var QueryConverterExtensionInterface[] */
    private array $converterExtensions = [];

    public function addExtension(QueryConverterExtensionInterface $extension): void
    {
        $this->converterExtensions[] = $extension;
    }

    public function convert(AbstractQueryDesigner $source): QueryBuilder
    {
        $this->tableAliasByColumn = [];

        $definition = $this->decodeDefinition($source->getDefinition());
        if (empty($definition['columns'])) {
            $definition['columns'] = [['name' => 'id']];
            $source->setDefinition($this->encodeDefinition($definition));
        }

        $qb = $this->doctrineHelper->getEntityManagerForClass($source->getEntity())->createQueryBuilder();
        $this->context()->setQueryBuilder($qb);
        $this->doConvert($source);

        foreach ($this->converterExtensions as $extension) {
            $this->tableAliasByColumn = ArrayUtil::arrayMergeRecursiveDistinct(
                $this->tableAliasByColumn,
                $extension->convert($source, $qb)
            );
        }

        return $qb;
    }

    public function getTableAliasByColumn(): array
    {
        return $this->tableAliasByColumn;
    }

    #[\Override]
    protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void {
        $this->context()->getQueryBuilder()->addSelect($columnExpr);
    }

    #[\Override]
    protected function saveTableAliases(array $tableAliases): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            if (\array_key_exists('table_identifier', $column)) {
                $columnName = $column['name'];
                $tableIdentifier = $column['table_identifier'];

                if ($context->hasVirtualColumnExpression($columnName)) {
                    $virtualColumnExpression = $context->getVirtualColumnExpression($columnName);
                    $exprColumn = explode('.', $virtualColumnExpression);
                    if (count($exprColumn) > 2) {
                        throw new \InvalidArgumentException('Unsupported virtual column');
                    }

                    $colsKey = QueryExpressionConverterInterface::MAPPING_COLUMNS;
                    $fieldName = $this->getFieldName($columnName);
                    $this->tableAliasByColumn[$colsKey][$tableIdentifier][$fieldName] = $virtualColumnExpression;

                    // Extract single table alias if virtual column contains function
                    preg_match('/\w+$/', $exprColumn[0], $matches);
                    $this->tableAliasByColumn[QueryExpressionConverterInterface::MAPPING_TABLES][$tableIdentifier]
                        = $matches[0];
                } else {
                    $this->tableAliasByColumn[QueryExpressionConverterInterface::MAPPING_TABLES][$tableIdentifier]
                        = $this->getTableAliasForColumn($columnName);
                }
            }
        }
    }

    #[\Override]
    protected function addWhereStatement(): void
    {
        // do nothing, conditions restrictions should be added in query compiler
    }

    #[\Override]
    protected function addGroupByColumn(string $columnAlias): void
    {
        // do nothing, grouping is not allowed
    }

    #[\Override]
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        // do nothing, order could not change results
    }

    #[\Override]
    protected function saveColumnAliases(array $columnAliases): void
    {
        // do nothing
    }
}
