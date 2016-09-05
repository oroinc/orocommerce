<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCheckoutBundle implements Migration, DatabasePlatformAwareInterface, RenameExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateCheckoutWorkflowDataQuery());

        $postSchema = clone $schema;

        $this->updateCheckoutTable($postSchema);
        $this->addCheckoutTableForeignKeys($postSchema, $queries);

        foreach ($this->getSchemaDiff($schema, $postSchema) as $query) {
            $queries->addPreQuery($query);
        }

        $queries->addPreQuery(new MoveCheckoutAddressDataQuery());

        $this->dropDefaultCheckoutTable($schema);

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                ['class_name' => 'Oro\Bundle\CheckoutBundle\Entity\Checkout'],
                ['class_name' => Type::STRING]
            )
        );
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_entity_config SET class_name = :new_class_name WHERE class_name = :class_name',
                [
                    'new_class_name' => 'Oro\Bundle\CheckoutBundle\Entity\Checkout',
                    'class_name' => 'Oro\Bundle\CheckoutBundle\Entity\BaseCheckout'
                ],
                ['new_class_name' => Type::STRING, 'class_name' => Type::STRING]
            )
        );

        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'orob2b_checkout', 'oro_checkout');
        $extension->renameTable($schema, $queries, 'orob2b_checkout_source', 'oro_checkout_source');
    }

    /**
     * @param Schema $schema
     */
    protected function updateCheckoutTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->dropColumn('checkout_discriminator');
        $table->dropColumn('checkout_type');
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', ['default' => true]);
        $table->addColumn('ship_to_billing_address', 'boolean', ['default' => false]);
        $table->addColumn('save_shipping_address', 'boolean', ['default' => true]);
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addUniqueIndex(['billing_address_id'], 'uniq_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_checkout_shipp_addr');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addCheckoutTableForeignKeys(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_checkout',
            'oro_order_address',
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_checkout',
            'oro_order_address',
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

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
