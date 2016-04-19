<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DeleteDefaultCheckoutFields implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->deleteDefaultCheckoutFieldsFromCheckout($schema);
    }

    /**
     * Delete default checkout fields from orob2b_checkout
     *
     * @param Schema $schema
     */
    protected function deleteDefaultCheckoutFieldsFromCheckout(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');

        $foreignKeys = $table->getForeignKeys();

        $removedFK = ['order_id', 'shipping_address_id', 'billing_address_id'];
        foreach ($foreignKeys as $foreignKey) {
            if (in_array($foreignKey->getLocalColumns()[0], $removedFK)) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }

        $table->dropColumn('order_id');
        $table->dropColumn('shipping_address_id');
        $table->dropColumn('billing_address_id');
        $table->dropColumn('save_billing_address');
        $table->dropColumn('ship_to_billing_address');
        $table->dropColumn('save_shipping_address');
    }
}
