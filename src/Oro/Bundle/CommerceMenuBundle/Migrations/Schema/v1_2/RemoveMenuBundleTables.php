<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveMenuBundleTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_menu_item');
        $schema->dropTable('oro_menu_item_title');

        $className = 'Oro\Bundle\MenuBundle\Entity\MenuItem';

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE entity_id IN ('
                . 'SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => $className],
                ['class' => 'string']
            )
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class',
                ['class' => $className],
                ['class' => 'string']
            )
        );
    }
}
