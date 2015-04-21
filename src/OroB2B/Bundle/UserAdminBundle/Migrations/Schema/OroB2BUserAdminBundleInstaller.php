<?php

namespace OroB2B\Bundle\UserAdminBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\UserAdminBundle\Migrations\Schema\v1_0\OroB2BUserAdminBundle as OroB2BUserAdminBundle10;

class OroB2BUserAdminBundleInstaller implements Installation
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
        $migration = new OroB2BUserAdminBundle10();
        $migration->up($schema, $queries);
    }
}
