<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        foreach ($table->getForeignKeys() as $fk) {
            if ($fk->getColumns() === ['payment_term_id']) {
                $table->removeForeignKey($fk->getName());
            }
        }
        $table->dropColumn('payment_term_id');

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
                ['class' => 'Oro\Bundle\SaleBundle\Entity\Quote', 'field_name' => 'paymentTerm']
            )
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                $configFieldSql,
                ['class' => 'Oro\Bundle\SaleBundle\Entity\Quote', 'field_name' => 'paymentTerm']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
