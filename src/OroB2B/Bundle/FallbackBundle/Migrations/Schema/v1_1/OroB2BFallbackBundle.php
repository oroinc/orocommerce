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
        $table->addIndex(['fallback'], 'idx_orob2b_fallback_fallback', []);
        $table->addIndex(['string'], 'idx_orob2b_fallback_string', []);
    }
}
