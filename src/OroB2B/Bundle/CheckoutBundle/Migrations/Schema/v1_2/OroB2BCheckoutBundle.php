<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCheckoutBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateCheckoutWorkflowDataQuery());

        $postSchema = clone $schema;

        $this->updateCheckoutTable($postSchema);
        $this->addCheckoutTableForeignKeys($postSchema);

        foreach ($this->getSchemaDiff($schema, $postSchema) as $query) {
            $queries->addPreQuery($query);
        }

        $queries->addPreQuery(new MoveCheckoutAddressDataQuery());

        $this->dropDefaultCheckoutTable($schema);

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                ['class_name' => 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout'],
                ['class_name' => Type::STRING]
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_entity_config SET class_name = :new_class_name WHERE class_name = :class_name',
                [
                    'new_class_name' => 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout',
                    'class_name' => 'OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout'
                ],
                ['new_class_name' => Type::STRING, 'class_name' => Type::STRING]
            )
        );
    }

    /**
     * @param Schema $schema
     */
    protected function updateCheckoutTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->dropColumn('checkout_discriminator');
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', ['default' => true]);
        $table->addColumn('ship_to_billing_address', 'boolean', ['default' => false]);
        $table->addColumn('save_shipping_address', 'boolean', ['default' => true]);
        $table->addUniqueIndex(['billing_address_id'], 'uniq_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_checkout_shipp_addr');
    }

    /**
     * @param Schema $schema
     */
    protected function addCheckoutTableForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function dropDefaultCheckoutTable(Schema $schema)
    {
        $schema->dropTable('orob2b_default_checkout');
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
