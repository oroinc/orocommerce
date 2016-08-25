<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

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

        // email to quote association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370aab0e4f0a0472d', 'oro_rel_26535370aab0e4f0b5ec88');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'quote_54b6ea15',
            'quote_54e154f7',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to quote association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d19aab0e4f0a0472d', 'oro_rel_46a29d19aab0e4f0b5ec88');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'quote_54b6ea15',
            'quote_54e154f7',
            RelationType::MANY_TO_MANY
        ));

        // attachments
        $attachments = $schema->getTable('oro_attachment');

        $attachments->removeForeignKey('FK_FA0FE0819F0665C6');
        $extension->renameColumn($schema, $queries, $attachments, 'quote_ea269983_id', 'quote_7de78df3_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'orob2b_sale_quote',
            ['quote_7de78df3_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'quote_ea269983',
            'quote_7de78df3',
            RelationType::MANY_TO_ONE
        ));

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('fk_oro_note_quote_ea269983_id');
        $extension->renameColumn($schema, $queries, $notes, 'quote_ea269983_id', 'quote_7de78df3_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_sale_quote',
            ['quote_7de78df3_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_oro_note_quote_7de78df3_id'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'quote_ea269983',
            'quote_7de78df3',
            RelationType::MANY_TO_ONE
        ));

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote', 'oro_sale_quote');
        $extension->renameTable($schema, $queries, 'orob2b_quote_address', 'oro_quote_address');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_prod_offer', 'oro_sale_quote_prod_offer');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_prod_request', 'oro_sale_quote_prod_request');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_product', 'oro_sale_quote_product');
        $extension->renameTable($schema, $queries, 'orob2b_quote_demand', 'oro_quote_demand');
        $extension->renameTable($schema, $queries, 'orob2b_quote_product_demand', 'oro_quote_product_demand');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
