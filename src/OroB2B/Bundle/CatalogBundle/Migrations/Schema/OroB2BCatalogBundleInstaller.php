<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_0\OroB2BCatalogBundle as OroB2BCatalogBundle10;
use OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_1\OroB2BCatalogBundle as OroB2BCatalogBundle11;

class OroB2BCatalogBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $migration10 = new OroB2BCatalogBundle10();
        $migration10->up($schema, $queries);

        $migration11 = new OroB2BCatalogBundle11();
        $migration11->up($schema, $queries);
    }
}
