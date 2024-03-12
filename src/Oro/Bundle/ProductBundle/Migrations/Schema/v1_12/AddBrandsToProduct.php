<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AddBrandsToProduct implements Migration, SlugExtensionAwareInterface, ExtendExtensionAwareInterface
{
    use SlugExtensionAwareTrait;
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
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
    private function createBrandTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_brand_created_at');
        $table->addIndex(['updated_at'], 'idx_oro_brand_updated_at');
    }

    /**
     * Create oro_brand_description table
     */
    private function createBrandDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_description');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_E42C1AB4EB576E89');
    }

    /**
     * Add oro_brand foreign keys.
     */
    private function addBrandForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_brand');
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
    private function createBrandNameTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_name');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_FA144D83EB576E89');
    }

    /**
     * Create oro_brand_short_desc table
     */
    private function createBrandShortDescTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_short_desc');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_70031058EB576E89');
    }

    /**
     * Create oro_brand_slug table
     */
    private function createBrandSlugTable(Schema $schema): void
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
    private function createBrandSlugPrototypeTable(Schema $schema): void
    {
        $this->slugExtension->addLocalizedSlugPrototypes($schema, 'oro_brand_slug_prototype', 'oro_brand', 'brand_id');
    }

    /**
     * Add oro_brand_description foreign keys.
     */
    private function addBrandDescriptionForeignKeys(Schema $schema): void
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
    private function addBrandNameForeignKeys(Schema $schema): void
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
    private function addBrandShortDescForeignKeys(Schema $schema): void
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

    private function addProductToBrand(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn('brand_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
