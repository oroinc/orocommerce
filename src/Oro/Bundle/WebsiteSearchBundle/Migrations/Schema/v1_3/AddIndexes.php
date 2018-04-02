<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Indexes for performance optimization
 */
class AddIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndexes($schema, 'oro_website_search_decimal');
        $this->addIndexes($schema, 'oro_website_search_integer');
        $this->addIndexes($schema, 'oro_website_search_datetime');
        $this->addIndexes($schema, 'oro_website_search_text');
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     */
    protected function addIndexes(Schema $schema, $tableName)
    {
        $table = $schema->getTable($tableName);

        $fieldIndexName = $tableName . '_field_idx';
        if (!$table->hasIndex($fieldIndexName)) {
            $table->addIndex(['field'], $fieldIndexName);
        }

        $itemFieldIndexName = $tableName . '_item_field_idx';
        if (!$table->hasIndex($itemFieldIndexName)) {
            $table->addIndex(['item_id', 'field'], $itemFieldIndexName);
        }
    }
}
