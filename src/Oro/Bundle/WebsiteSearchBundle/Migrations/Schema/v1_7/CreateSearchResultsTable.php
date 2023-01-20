<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Create table to store search results.
 */
class CreateSearchResultsTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_website_search_result');
        $table->addColumn('id', 'integer', ['autoincrement' => true, 'unsigned' => true]);
        $table->addColumn('organization_id', 'integer', []);
        $table->addColumn('business_unit_owner_id', 'integer', []);
        $table->addColumn('search_term', 'text', []);
        $table->addColumn('result_type', 'string', ['length' => 255]);
        $table->addColumn('result', 'integer', ['unsigned' => true]);
        $table->addColumn('result_details', 'text', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('localization_id', 'integer', []);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['search_term'], 'idx_searchresults_term', []);
        $table->addIndex(['organization_id'], 'idx_searchresults_org_id', []);
        $table->addIndex(['customer_id'], 'idx_searchresults_customer_id', []);
        $table->addIndex(['website_id'], 'idx_searchresults_website_id', []);
        $table->addIndex(['customer_user_id'], 'idx_searchresults_customer_user_id', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
