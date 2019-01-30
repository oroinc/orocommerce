<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Change ON DELETE to CASCADE for price rule - product unit relation
 */
class PriceRuleMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $fkName = 'FK_B8DA674C29646BBD';
        $table = $schema->getTable('oro_price_rule');
        $fk = $table->getForeignKey($fkName);

        if ($fk->getOption('onDelete') !== 'CASCADE') {
            $table->removeForeignKey($fkName);
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_product_unit'),
                ['product_unit_id'],
                ['code'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }
    }
}
