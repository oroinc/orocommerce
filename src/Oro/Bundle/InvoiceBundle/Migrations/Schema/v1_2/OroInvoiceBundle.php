<?php

namespace Oro\Bundle\InvoiceBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroInvoiceBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
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
        $this->updateInvoiceTable($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function updateInvoiceTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_invoice');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_id',
            'customer_id'
        );
    }

    /**
     * Sets the RenameExtension
     *
     * @param RenameExtension $renameExtension
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
