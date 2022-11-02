<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AddBrandsToProduct implements Migration, SlugExtensionAwareInterface, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'oro_product';
    const BRAND_TABLE_NAME = 'oro_brand';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @inheritdoc
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createBrandTable($schema);
        $this->createBrandDescriptionTable($schema);
        $this->createBrandNameTable($schema);
        $this->createBrandShortDescTable($schema);
        $this->createBrandSlugTable($schema);
        $this->createBrandSlugPrototypeTable($schema);

        /** Foreign keys generation **/
        $this->addBrandForeignKeys($schema);
        $this->addBrandDescriptionForeignKeys($schema);
        $this->addBrandNameForeignKeys($schema);
        $this->addBrandShortDescForeignKeys($schema);

        /** ProductToBrand relation */
        $this->addProductToBrand($schema);
    }

    /**
     * Create oro_brand table
     */
    protected function createBrandTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_brand_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_brand_updated_at', []);
    }

    /**
     * Create oro_brand_description table
     */
    protected function createBrandDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_description');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_E42C1AB4EB576E89');
    }

    /**
     * Add oro_brand foreign keys.
     */
    protected function addBrandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::BRAND_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_brand_name table
     */
    protected function createBrandNameTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_name');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_FA144D83EB576E89');
    }

    /**
     * Create oro_brand_short_desc table
     */
    protected function createBrandShortDescTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_short_desc');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_70031058EB576E89');
    }

    /**
     * Create oro_brand_slug table
     */
    protected function createBrandSlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_brand_slug',
            'oro_brand',
            'brand_id'
        );
    }

    /**
     * Create oro_brand_slug_prototype table
     */
    protected function createBrandSlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_brand_slug_prototype',
            'oro_brand',
            'brand_id'
        );
    }

    /**
     * Add oro_brand_description foreign keys.
     */
    protected function addBrandDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_brand_description');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_brand_name foreign keys.
     */
    protected function addBrandNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_brand_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_brand_short_desc foreign keys.
     */
    protected function addBrandShortDescForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_brand_short_desc');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    public function addProductToBrand(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('brand_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
