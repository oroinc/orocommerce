<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

class OroB2BOrderBundle implements Migration, AttachmentExtensionAwareInterface
{
    const ORDER_TABLE_NAME = 'orob2b_order';

    /** @var  AttachmentExtension */
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
        self::addAttachmentAssociations($schema, $this->attachmentExtension);
    }

    /**
     * Enable attachments for Order entity
     *
     * @param Schema $schema
     */
    public static function addAttachmentAssociations(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addAttachmentAssociation(
            $schema,
            self::ORDER_TABLE_NAME
        );
    }
}