<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_0\OroB2BCustomerBundle as OroB2BCustomerBundle10;

class OroB2BCustomerBundleInstaller implements Installation
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
        $migration = new OroB2BCustomerBundle10();
        $migration->up($schema, $queries);
    }
}
