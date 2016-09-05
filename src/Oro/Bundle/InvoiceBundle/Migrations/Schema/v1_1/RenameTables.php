<?php

namespace Oro\Bundle\InvoiceBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'orob2b_invoice', 'oro_invoice');
        $extension->renameTable($schema, $queries, 'orob2b_invoice_line_item', 'oro_invoice_line_item');

        $schema->getTable('orob2b_invoice')->dropIndex('orob2b_invoice_created_at_index');
        $extension->addIndex($schema, $queries, 'oro_invoice', ['created_at'], 'oro_invoice_created_at_index');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
