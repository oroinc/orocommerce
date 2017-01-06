<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new UpdateAnonymousUserRoleQuery());
        $queries->addQuery(new AddFrontendAnonymousUserRoleQuery());
        $queries->addQuery(new MigrateFrontendAnonymousUserRolePermissionsQuery());
    }
}
