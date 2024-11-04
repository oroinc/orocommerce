<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateTaxValueRenameResultMigration implements
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
            $schema->getTable('oro_tax_value'),
            'result',
            'result_base64'
        );
    }
}
