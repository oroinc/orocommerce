<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_0\OroB2BWebsiteBundle as OroB2BWebsiteBundle10;

class OroB2BWebsiteBundleInstaller implements Installation
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
        $migration = new OroB2BWebsiteBundle10();
        $migration->up($schema, $queries);
    }
}
