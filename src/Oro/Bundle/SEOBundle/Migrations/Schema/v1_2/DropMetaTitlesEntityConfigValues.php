<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropMetaTitlesEntityConfigValues implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $configIndexValueSql = <<<'SQL'
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
SQL;

        $configFieldSql = <<<'SQL'
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
SQL;

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            $configIndexValueSql,
            ['class' => 'Oro\Bundle\ProductBundle\Entity\Product', 'field_name' => 'metaTitle']
        ));
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            $configFieldSql,
            ['class' => 'Oro\Bundle\ProductBundle\Entity\Product', 'field_name' => 'metaTitle']
        ));
    }
}
