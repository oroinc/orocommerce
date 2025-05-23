<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTransactionOptionsToOld implements
    Migration,
    OrderedMigrationInterface,
    RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 100;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $schema->getTable('oro_payment_transaction'),
            'transaction_options',
            'transaction_options_old'
        );
    }
}
