<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroInventoryBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(new RenameInventoryConfigSectionQuery('oro_warehouse', 'oro_inventory', 'manage_inventory'));
    }
}
