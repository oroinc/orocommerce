<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ScopeBundle\Entity\Scope',
                'account',
                'datagrid',
                'is_visible',
                DatagridScope::IS_VISIBLE_FALSE
            )
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ScopeBundle\Entity\Scope',
                'accountGroup',
                'datagrid',
                'is_visible',
                DatagridScope::IS_VISIBLE_FALSE
            )
        );
    }
}
