<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroOrderAddressTable($schema, $queries);
    }

    private function updateOroOrderAddressTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order_address');

        $fkAccountAddress = $this->getConstraintName($table, 'account_address_id');
        $table->removeForeignKey($fkAccountAddress);

        $fkAccountUserAddress = $this->getConstraintName($table, 'account_user_address_id');
        $table->removeForeignKey($fkAccountUserAddress);

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_address_id',
            'customer_address_id'
        );

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_address_id',
            'customer_user_address_id'
        );
    }

    /**
     * Sets the RenameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }
}
