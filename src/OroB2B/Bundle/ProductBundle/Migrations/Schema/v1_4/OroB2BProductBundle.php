<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements
    Migration,
    OrderedMigrationInterface,
    AttachmentExtensionAwareInterface,
    ContainerAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_IMAGE_TABLE_NAME = 'orob2b_product_image';
    const PRODUCT_IMAGE_TYPE_TABLE_NAME = 'orob2b_product_image_type';
    const PRODUCT_IMAGE_TO_IMAGE_TYPE_TABLE_NAME = 'orob2b_product_image_to_type';
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
        $this->createOroB2BProductImageTable($schema);
        $this->createOroB2BProductImageTypeTable($schema);
        $this->createOrob2BProductImageToImageType($schema);

        $this->addOroB2BProductImageForeignKeys($schema);
        $this->addOroB2BProductImageToImageTypeForeignKeys($schema);

        $this->addAttachmentAssociations($schema);
        $this->insertImageTypes($queries);
        $this->migrateImages($queries);
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
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['type'], 'product_image_type__type__uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductImageToImageType(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TO_IMAGE_TYPE_TABLE_NAME);
        $table->addColumn('product_image_id', 'integer', []);
        $table->addColumn('product_image_type_id', 'integer', []);
        $table->setPrimaryKey(['product_image_id', 'product_image_type_id']);
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
    protected function addOroB2BProductImageToImageTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TO_IMAGE_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME),
            ['product_image_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME),
            ['product_image_type_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
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

        $migrateImageTypesSqlMask = 'INSERT INTO %s (product_image_id, product_image_type_id)
                                     SELECT product.image_id, image_type.id FROM %s product
                                     CROSS JOIN %s image_type
                                     WHERE product.%s IS NOT NULL';

        $queries->addPostQuery(
            sprintf(
                $migrateImageTypesSqlMask,
                self::PRODUCT_IMAGE_TO_IMAGE_TYPE_TABLE_NAME,
                self::PRODUCT_TABLE_NAME,
                self::PRODUCT_IMAGE_TYPE_TABLE_NAME,
                self::PRODUCT_IMAGE_FIELD_NAME
            )
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function insertImageTypes(QueryBag $queries)
    {
        $imageTypeProvider = $this->container->get('oro_layout.provider.image_type');

        foreach ($imageTypeProvider->getImageTypes() as $imageType) {
            $queries->addPostQuery(
                sprintf(
                    "INSERT INTO %s (type) VALUES ('%s')",
                    self::PRODUCT_IMAGE_TYPE_TABLE_NAME,
                    $imageType->getName()
                )
            );
        }
    }
}
