<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropOldPriceListRelationTables implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function getOrder()
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('orob2b_price_list_to_account');
        $schema->dropTable('orob2b_price_list_to_website');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            CreatePriceListRelationWithPriorityTables::TMP_RELATION_ACCOUNT_TABLE_NAME,
            'orob2b_price_list_to_account'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            CreatePriceListRelationWithPriorityTables::TMP_RELATION_WEBSITE_TABLE_NAME,
            'orob2b_price_list_to_website'
        );
    }
}
