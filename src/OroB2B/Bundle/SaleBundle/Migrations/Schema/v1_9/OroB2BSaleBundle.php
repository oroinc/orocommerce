<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSaleBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->enableQuoteExpiredProcess($schema, $queries);
        $this->removeQuoteAddressSerializedDataColumn($schema);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function enableQuoteExpiredProcess(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_process_definition')) {
            return;
        }

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_process_definition SET enabled = TRUE WHERE name = :name',
            ['name' => 'expire_quotes']
        ));
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function removeQuoteAddressSerializedDataColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_address');
        if ($table->hasColumn('serialized_data') &&
            !class_exists('Oro\Bundle\EntitySerializedFieldsBundle\OroEntitySerializedFieldsBundle')
        ) {
            $table->dropColumn('serialized_data');
        }
    }
}
