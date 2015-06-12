<?php
namespace OroB2B\Bundle\OrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_0\OroB2BOrderBundle as OroB2BOrderBundle10;

class OroB2BOrderBundleInstaller implements Installation
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
        $migration = new OroB2BOrderBundle10();
        $migration->up($schema, $queries);
    }
}
