<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveImageRelationOnProduct implements Migration, AttachmentExtensionAwareInterface, OrderedMigrationInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_IMAGE_FIELD = 'image_id';

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        $productTable = $schema->getTable(self::PRODUCT_TABLE_NAME);
        if ($productTable->hasColumn(self::PRODUCT_IMAGE_FIELD)) {
            $productTable->dropColumn(self::PRODUCT_IMAGE_FIELD);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
