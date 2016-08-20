<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveStatusEnumField implements Migration, OrderedMigrationInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->changeColumn('status', ['notnull' => true]);
        $table->dropColumn('status_id');
    }
}
