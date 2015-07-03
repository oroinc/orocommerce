<?php
namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\ShoppingListBundle\Migrations\Schema\v1_0\OroB2BShoppingListBundle as OroB2BShoppingListBundle10;

class OroB2BShoppingListBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration = new OroB2BShoppingListBundle10();
        $migration->up($schema, $queries);
    }
}
