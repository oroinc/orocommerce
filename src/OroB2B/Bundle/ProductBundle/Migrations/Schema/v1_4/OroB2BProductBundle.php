<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface,
    AttachmentExtensionAwareInterface,
    ContainerAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'orob2b_product_unit_precision';
    const PRODUCT_IMAGE_TABLE_NAME = 'orob2b_product_image';
    const PRODUCT_IMAGE_TYPE_TABLE_NAME = 'orob2b_product_image_type';
    const PRODUCT_IMAGE_FIELD_NAME = 'image_id';
    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
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
        $this->updateOroB2BProductUnitPrecisionTable($schema);
        $this->updateOroB2BProductTable($schema);
        $this->addOroB2BProductForeignKeys($schema);
        
        $this->createOroB2BProductImageTable($schema);
        $this->createOroB2BProductImageTypeTable($schema);

        $this->addOroB2BProductImageForeignKeys($schema);
        $this->addOroB2BProductImageTypeForeignKeys($schema);

        $this->addAttachmentAssociations($schema);
        $this->migrateImages($queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $foreignTable
     * @param array $fields
     */
    protected function createConstraint(Schema $schema, QueryBag $queries, $tableName, $foreignTable, array $fields)
    {
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
     *
     * @param Schema $schema
     */
    protected function updateOroB2BProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => false]);
    }

    /**
     * Update orob2b_product table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BProductTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['primary_unit_precision_id'], 'idx_orob2b_product_primary_unit_precision_id');
    }

    /**
     * Add orob2b_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductImageTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductImageTypeTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductImageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductImageTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME),
            ['product_image_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addAttachmentAssociations(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_IMAGE_TABLE_NAME,
            'image',
            [
                'importexport' => ['excluded' => true]
            ],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function migrateImages(QueryBag $queries)
    {
        $migrateImagesSqlMask = 'INSERT INTO %1$s (product_id, %2$s)
                                 SELECT id, %2$s FROM %3$s
                                 WHERE %2$s IS NOT NULL';

        $queries->addPostQuery(
            sprintf(
                $migrateImagesSqlMask,
                self::PRODUCT_IMAGE_TABLE_NAME,
                self::PRODUCT_IMAGE_FIELD_NAME,
                self::PRODUCT_TABLE_NAME
            )
        );

        $migrateImageTypesSqlMask = 'INSERT INTO %s (product_image_id, type)
                                     SELECT product.image_id, types.type FROM %s product
                                     CROSS JOIN (%s) types
                                     WHERE product.%s IS NOT NULL';

        $queries->addPostQuery(
            sprintf(
                $migrateImageTypesSqlMask,
                self::PRODUCT_IMAGE_TYPE_TABLE_NAME,
                self::PRODUCT_TABLE_NAME,
                $this->getImageTypesSubSelect(),
                self::PRODUCT_IMAGE_FIELD_NAME
            )
        );
    }

    /**
     * @return string
     */
    protected function getImageTypesSubSelect()
    {
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');
        $selects = [];

        foreach ($imageTypeProvider->getImageTypes() as $imageType) {
            $selects[] = sprintf('SELECT \'%s\' as type', $imageType->getName());
        }

        return join(' UNION ', $selects);
    }
}
