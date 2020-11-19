<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add price list relation to CheckoutSubtotal entity to enable support of flat pricing storage.
 */
class AddFlatPriceListToCheckoutSubtotal implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_checkout_subtotal');
        if ($table->hasColumn('price_list_id')) {
            return;
        }

        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
