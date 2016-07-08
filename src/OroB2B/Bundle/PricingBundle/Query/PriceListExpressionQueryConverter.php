<?php

namespace OroB2B\Bundle\PricingBundle\Query;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter;

class PriceListExpressionQueryConverter extends SegmentQueryConverter
{
    /**
     * @var array
     */
    protected $tableAliasByColumn = [];

    /**
     * @param AbstractQueryDesigner $source
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function convert(AbstractQueryDesigner $source)
    {
        /** @var array $definition */
        $definition = json_decode($source->getDefinition(), JSON_OBJECT_AS_ARRAY);
        if (!array_key_exists('columns', $definition)) {
            $definition['columns'] = [['name' => 'id']];
            $source->setDefinition($definition);
        }

        // TODO: Add restriction builder to build WHERE part based on condition Node tree

        return parent::convert($source);
    }

    /**
     * @return array
     */
    public function getTableAliasByColumn()
    {
        return $this->tableAliasByColumn;
    }

    protected function saveTableAliases($tableAliases)
    {
        foreach ($this->definition['columns'] as $column) {
            $columnName = $column['name'];
            $this->tableAliasByColumn[$columnName] = $this->getTableAliasForColumn($columnName);
        }
    }
}
