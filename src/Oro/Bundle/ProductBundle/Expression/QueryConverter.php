<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryBuilderGroupingOrmQueryConverter;

/**
 * Converts a price list query definition created by the query designer to an ORM query.
 */
class QueryConverter extends QueryBuilderGroupingOrmQueryConverter
{
    /** @var array */
    private $tableAliasByColumn = [];

    /** @var QueryConverterExtensionInterface[] */
    private $converterExtensions = [];

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
            $this->tableAliasByColumn = array_merge(
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function saveTableAliases(array $tableAliases): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            if (\array_key_exists('table_identifier', $column)) {
                $columnName = $column['name'];
                $tableIdentifier = $column['table_identifier'];

                if ($context->hasVirtualColumnExpression($columnName)) {
                    $exprColumn = explode('.', $context->getVirtualColumnExpression($columnName));
                    $this->tableAliasByColumn[$tableIdentifier] = $exprColumn[0];
                } else {
                    $this->tableAliasByColumn[$tableIdentifier] = $this->getTableAliasForColumn($columnName);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addWhereStatement(): void
    {
        // do nothing, conditions restrictions should be added in query compiler
    }

    /**
     * {@inheritdoc}
     */
    protected function addGroupByColumn(string $columnAlias): void
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn(string $columnAlias, string $columnSorting): void
    {
        // do nothing, order could not change results
    }

    /**
     * {@inheritdoc}
     */
    protected function saveColumnAliases(array $columnAliases): void
    {
        // do nothing
    }
}
