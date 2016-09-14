<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;

class OroShoppingListBundle implements Migration, RenameExtensionAwareInterface, DatabasePlatformAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @var AbstractPlatform
     */
    private $databasePlatform;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'orob2b_shopping_list_total', 'oro_shopping_list_total');
        $extension->renameTable($schema, $queries, 'orob2b_shopping_list', 'oro_shopping_list');
        $extension->renameTable($schema, $queries, 'orob2b_shopping_list_line_item', 'oro_shopping_list_line_item');

        if ($this->databasePlatform->supportsSequences()) {
            $oldSequenceName = $this->databasePlatform->getIdentitySequenceName('orob2b_shopping_list_total', 'id');
            $newSequenceName = $this->databasePlatform->getIdentitySequenceName('oro_shopping_list_total', 'id');
            if (!$schema->hasSequence($oldSequenceName)) {
                $renameSequenceQuery = new SqlSchemaUpdateMigrationQuery(
                    "ALTER SEQUENCE $oldSequenceName RENAME TO $newSequenceName"
                );
                $queries->addQuery($renameSequenceQuery);
            }
        }

        $schema->getTable('orob2b_shopping_list')->dropIndex('orob2b_shop_lst_created_at_idx');
        $schema->getTable('orob2b_shopping_list_line_item')->dropIndex('orob2b_shopping_list_line_item_uidx');

        $extension->addIndex($schema, $queries, 'oro_shopping_list', ['created_at'], 'oro_shop_lst_created_at_idx');
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_shopping_list_line_item',
            ['product_id', 'shopping_list_id', 'unit_code'],
            'orob2b_shopping_list_line_item_uidx'
        );

        $queries->addPostQuery(new RenameConfigSectionQuery('oro_b2b_shopping_list', 'oro_shopping_list'));
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->databasePlatform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
