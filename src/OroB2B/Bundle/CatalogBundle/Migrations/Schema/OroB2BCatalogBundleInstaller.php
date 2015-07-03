<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_0\OroB2BCatalogBundle as OroB2BCatalogBundle;

class OroB2BCatalogBundleInstaller implements Installation
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
        $migration = new OroB2BCatalogBundle();
        $migration->up($schema, $queries);
    }
}
