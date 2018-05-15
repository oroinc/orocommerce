<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
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
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addColumn('payment_term_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_term'),
            ['payment_term_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

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

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote', 'oro_sale_quote');
        $extension->renameTable($schema, $queries, 'orob2b_quote_address', 'oro_quote_address');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_prod_offer', 'oro_sale_quote_prod_offer');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_prod_request', 'oro_sale_quote_prod_request');
        $extension->renameTable($schema, $queries, 'orob2b_sale_quote_product', 'oro_sale_quote_product');
        $extension->renameTable($schema, $queries, 'orob2b_quote_demand', 'oro_quote_demand');
        $extension->renameTable($schema, $queries, 'orob2b_quote_product_demand', 'oro_quote_product_demand');

        // system configuration
        $queries->addPostQuery(new RenameConfigSectionQuery('oro_b2b_sale', 'oro_sale'));
    }

    /**
     * Should be executed before:
     * @see \Oro\Bundle\SaleBundle\Migrations\Schema\v1_10\MigrateNotes
     *
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
