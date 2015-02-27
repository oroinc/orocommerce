<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_0\OroB2BFallbackBundle as OroB2BFallbackBundle10;

class OroB2BFallbackBundleInstaller implements Installation
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
        $migration = new OroB2BFallbackBundle10();
        $migration->up($schema, $queries);
    }
}
