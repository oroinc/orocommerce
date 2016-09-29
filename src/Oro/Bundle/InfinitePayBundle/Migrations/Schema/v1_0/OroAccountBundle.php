<?php

namespace Oro\Bundle\InfinitePayBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundle implements Migration, OrderedMigrationInterface
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_customer');
        $table->addColumn(
            'vat_id',
            'text',
            [
                'oro_options' => [
                    'extend'   => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'merge'    => ['display' => true],
                ]
            ]
        );
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
