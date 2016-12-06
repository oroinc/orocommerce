<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class RemoveColumnHasVariantsFromProduct implements Migration
{
    const PRODUCT_TABLE_NAME = 'oro_product';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->dropColumn('has_variants');

        $configIndexValueSql = <<<QUERY
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
QUERY;

        $configFieldSql = <<<QUERY
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
QUERY;

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configIndexValueSql,
                ['class' => Product::class, 'field_name' => 'hasVariants']
            )
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configFieldSql,
                ['class' => Product::class, 'field_name' => 'hasVariants']
            )
        );
    }
}
