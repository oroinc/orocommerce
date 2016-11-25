<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWebsiteBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ScopeBundle\Entity\Scope',
                'website',
                'datagrid',
                'is_visible',
                DatagridScope::IS_VISIBLE_FALSE
            )
        );
    }
}
