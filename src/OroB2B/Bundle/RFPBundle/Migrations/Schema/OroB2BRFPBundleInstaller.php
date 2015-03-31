<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_0\OroB2BRFPBundle as OroB2BRFPBundle10;

class OroB2BRFPBundleInstaller implements Installation
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
        $migration = new OroB2BRFPBundle10();
        $migration->up($schema, $queries);
    }
}
