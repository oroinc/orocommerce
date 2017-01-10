<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateTaxValueConvertResultsMigration implements
    Migration,
    OrderedMigrationInterface,
    DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 200;
    }

    /**
     * {@inheritdoc}
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schemaWithNewColumn = clone $schema;
        $schemaWithNewColumn->getTable('oro_tax_value')->addColumn('result', 'json_array', ['notnull' => false]);
        foreach ($this->getSchemaDiff($schema, $schemaWithNewColumn) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(new ConvertTaxValueResultsQuery($this->platform));

        $schemaWithModifiedColumn = clone $schemaWithNewColumn;
        $schemaWithModifiedColumn->getTable('oro_tax_value')->changeColumn('result', ['notnull' => true]);
        $schemaWithModifiedColumn->getTable('oro_tax_value')->dropColumn('result_base64');
        foreach ($this->getSchemaDiff($schemaWithNewColumn, $schemaWithModifiedColumn) as $query) {
            $queries->addQuery($query);
        }
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();
        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }
}
