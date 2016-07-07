<?php

namespace OroB2B\Bundle\FrontendBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migrations\Schema\v1_0\UpdateNamespacesAndTranslationsQuery;

class OroB2BFrontendBundleInstaller implements Installation
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
        // TODO: remove this call after stable release
        $queries->addPreQuery(new UpdateNamespacesAndTranslationsQuery());
    }
}
