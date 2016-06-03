<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BFallbackBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_fallback_locale_value');
        $table->renameIndex('idx_orob2b_fallback_fallback', 'idx_fallback');
        $table->renameIndex('idx_orob2b_fallback_string', 'idx_string');

        $schema->dropTable('oro_fallback_localization_val');
        $queries->addQuery('RENAME TABLE `orob2b_fallback_locale_value` TO `oro_fallback_localization_val`');
    }
}
