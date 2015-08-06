<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration, ExtendExtensionAwareInterface
{
    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCategoryVisibilityTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCategoryVisibilityForeignKeys($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Create orob2b_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->extendExtension->addEnumField(
            $schema,
            self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME,
            'visibility',
            'category_visibility'
        );
    }

    /**
     * Add orob2b_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
