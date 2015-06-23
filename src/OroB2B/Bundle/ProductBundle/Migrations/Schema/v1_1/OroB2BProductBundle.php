<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BProductBundle implements
    Migration,
    ExtendExtensionAwareInterface,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    const TABLE_NAME = 'orob2b_product';
    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;
    const MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB = 5;

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var NoteExtension */
    protected $noteExtension;

    /** @var AttachmentExtension */
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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateProductTable($schema);
        $this->addNoteAssociations($schema);
        $this->addAttachmentAssociations($schema);
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    protected function updateProductTable(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            self::TABLE_NAME,
            'inventory_status',
            'prod_inventory_status'
        );

        $this->extendExtension->addEnumField(
            $schema,
            self::TABLE_NAME,
            'visibility',
            'prod_visibility'
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, self::TABLE_NAME);
    }

    /**
     * @param Schema $schema
     */
    protected function addAttachmentAssociations(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'image',
            [],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            [],
            self::MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB
        );
    }
}
