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
        $table = $schema->createTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('types', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);

        //foreign key
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        //image relation
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_IMAGE_TABLE_NAME,
            'image',
            [
                'importexport' => ['excluded' => true]
            ],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );

        //migrate old image field to product image with types
        $queries->addPostQuery(new MigrateImageToProductImageQuery(
            $this->container->get('oro_entity.doctrine_helper'),
            $this->container->get('oro_layout.provider.image_type'),
            $this->container->getParameter('orob2b_product.product.class'),
            $this->container->getParameter('orob2b_product.product_image.class')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
