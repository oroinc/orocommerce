<?php

namespace OroB2B\Bundle\AttributeBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\AttributeBundle\Migrations\Schema\v1_0\OroB2BAttributeBundle as OroB2BAttributeBundle10;

class OroB2BAttributeBundleInstaller implements Installation
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
        $migration = new OroB2BAttributeBundle10();
        $migration->up($schema, $queries);
    }
}
