<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\CMSBundle\Migrations\Schema\v1_0\OroB2BCMSBundle as OroB2BCMSBundle10;

class OroB2BCMSBundleInstaller implements Installation
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
        $migration = new OroB2BCMSBundle10();
        $migration->up($schema, $queries);
    }
}
