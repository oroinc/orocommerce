<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddProductSuggestion implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroSuggestionTable($schema);
        $this->createOroProductSuggestionProductTable($schema);

        $this->addOroSuggestionForeignKeys($schema);
        $this->addOroProductSuggestionForeignKeys($schema);
    }

    private function createOroSuggestionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_website_search_suggestion');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('phrase', 'string');
        $table->addColumn('words_count', 'smallint');
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer');
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->addUniqueIndex(['phrase', 'localization_id', 'organization_id'], 'suggestion_unique');
        $table->setPrimaryKey(['id']);
    }

    private function createOroProductSuggestionProductTable(Schema $schema)
    {
        $table = $schema->createTable('oro_website_search_suggestion_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('suggestion_id', 'integer');
        $table->addColumn('product_id', 'integer');

        $table->addUniqueIndex(['suggestion_id', 'product_id'], 'product_suggestion_unique');
        $table->setPrimaryKey(['id']);
    }

    private function addOroSuggestionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_website_search_suggestion');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroProductSuggestionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_website_search_suggestion_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website_search_suggestion'),
            ['suggestion_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
