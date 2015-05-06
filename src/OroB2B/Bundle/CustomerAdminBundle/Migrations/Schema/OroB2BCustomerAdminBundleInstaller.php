<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\CustomerAdminBundle\Migrations\Schema\v1_0\OroB2BCustomerAdminBundle as OroB2BCustomerAdminBundle10;

class OroB2BCustomerAdminBundleInstaller implements Installation
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
        $migration = new OroB2BCustomerAdminBundle10();
        $migration->up($schema, $queries);
    }
}
