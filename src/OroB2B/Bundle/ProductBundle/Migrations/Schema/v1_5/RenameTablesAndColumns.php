<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // attachments
        $attachments = $schema->getTable('oro_attachment');

        $attachments->removeForeignKey('FK_FA0FE08144945F67');
        $extension->renameColumn($schema, $queries, $attachments, 'product_7cf459b8_id', 'product_f4309915_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'orob2b_product',
            ['product_f4309915_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\AttachmentBundle\Entity\Attachment',
            'OroB2B\Bundle\ProductBundle\Entity\Product',
            'product_7cf459b8',
            'product_f4309915',
            RelationType::MANY_TO_ONE
        ));

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE144945F67');
        $extension->renameColumn($schema, $queries, $notes, 'product_7cf459b8_id', 'product_f4309915_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_product',
            ['product_f4309915_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\ProductBundle\Entity\Product',
            'product_7cf459b8',
            'product_f4309915',
            RelationType::MANY_TO_ONE
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
