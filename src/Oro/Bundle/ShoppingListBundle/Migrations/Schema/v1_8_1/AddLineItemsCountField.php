<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_8_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddLineItemsCountField implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_shopping_list');
        if (!$table->hasColumn('line_items_count')) {
            $table->addColumn('line_items_count', 'smallint', [
                'default' => 0,
                'unsigned' => true,
                OroOptions::KEY => [
                    'entity' => ['label' => 'oro.shoppinglist.line_items_count.label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_SYSTEM,
                    ]
                ]
            ]);
        }
    }
}
