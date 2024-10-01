<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigArrayKeyQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenamePriority implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    const OLD_COLUMN_NAME = 'priority';
    const NEW_COLUMN_NAME = 'sort_order';

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renamePriorityColumn($schema, $queries, 'oro_price_list_to_cus_group');
        $this->renamePriorityColumn($schema, $queries, 'oro_price_list_to_customer');
        $this->renamePriorityColumn($schema, $queries, 'oro_price_list_to_website');

        $queries->addQuery(new RenameConfigArrayKeyQuery(
            'oro_pricing',
            'default_price_lists',
            self::OLD_COLUMN_NAME,
            self::NEW_COLUMN_NAME
        ));
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     */
    protected function renamePriorityColumn(Schema $schema, QueryBag $queries, $tableName)
    {
        $table = $schema->getTable($tableName);

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            self::OLD_COLUMN_NAME,
            self::NEW_COLUMN_NAME
        );
    }
}
