<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroProductBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface,
    AttachmentExtensionAwareInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;
    use RenameExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_product_description',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_product_name',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_product_short_desc',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->updateOroProductUnitPrecisionTable($schema, $queries);
        $this->updateOroProductTable($schema);
        $this->addOroProductForeignKeys($schema);

        $this->createOroProductImageTable($schema);
        $this->createOroProductImageTypeTable($schema);

        $this->addOroProductImageForeignKeys($schema);
        $this->addOroProductImageTypeForeignKeys($schema);

        $this->addAttachmentAssociations($schema);
        $this->migrateImages($queries);
    }

    private function createConstraint(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        string $foreignTable,
        array $fields
    ): void {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            $tableName,
            $foreignTable,
            $fields,
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Update orob2b_product_unit_precision table
     */
    private function updateOroProductUnitPrecisionTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('orob2b_product_unit_precision');
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_product_unit_precision SET conversion_rate = :conversion_rate, sell = :sell',
                [
                    'conversion_rate' => 1.0,
                    'sell' => true,
                ],
                [
                    'conversion_rate' => Types::FLOAT,
                    'sell' => Types::BOOLEAN
                ]
            )
        );
    }

    /**
     * Update orob2b_product table
     */
    private function updateOroProductTable(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product');
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['primary_unit_precision_id'], 'idx_orob2b_product_primary_unit_precision_id');
    }

    /**
     * Add orob2b_product foreign keys.
     */
    private function addOroProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit_precision'),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function createOroProductImageTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_image');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }

    private function createOroProductImageTypeTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_image_type');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroProductImageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_image');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroProductImageTypeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_image_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_image'),
            ['product_image_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    private function addAttachmentAssociations(Schema $schema): void
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'orob2b_product_image',
            'image',
            [
                'importexport' => ['excluded' => true]
            ],
            10
        );
    }

    private function migrateImages(QueryBag $queries): void
    {
        $migrateImagesSqlMask = 'INSERT INTO %1$s (product_id, %2$s)
                                 SELECT id, %2$s FROM %3$s
                                 WHERE %2$s IS NOT NULL';
        $queries->addPostQuery(sprintf(
            $migrateImagesSqlMask,
            'orob2b_product_image',
            'image_id',
            'orob2b_product'
        ));

        $migrateImageTypesSqlMask = 'INSERT INTO %s (product_image_id, type)
                                     SELECT product.image_id, types.type FROM %s product
                                     CROSS JOIN (%s) types
                                     WHERE product.%s IS NOT NULL';
        $queries->addPostQuery(sprintf(
            $migrateImageTypesSqlMask,
            'orob2b_product_image_type',
            'orob2b_product',
            $this->getImageTypesSubSelect(),
            'image_id'
        ));
    }

    private function getImageTypesSubSelect(): string
    {
        $selects = [];
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');
        foreach ($imageTypeProvider->getImageTypes() as $imageType) {
            $selects[] = sprintf('SELECT \'%s\' as type', $imageType->getName());
        }

        return implode(' UNION ', $selects);
    }
}
