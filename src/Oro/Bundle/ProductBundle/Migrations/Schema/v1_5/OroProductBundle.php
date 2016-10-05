<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class OroProductBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
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
        $table = $schema->getTable('orob2b_product_image');
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);

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
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\ProductBundle\Entity\Product',
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
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\ProductBundle\Entity\Product',
            'product_7cf459b8',
            'product_f4309915',
            RelationType::MANY_TO_ONE
        ));

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_product', 'oro_product');
        $extension->renameTable($schema, $queries, 'orob2b_product_name', 'oro_product_name');
        $extension->renameTable($schema, $queries, 'orob2b_product_description', 'oro_product_description');
        $extension->renameTable($schema, $queries, 'orob2b_product_short_desc', 'oro_product_short_desc');
        $extension->renameTable($schema, $queries, 'orob2b_product_unit', 'oro_product_unit');
        $extension->renameTable($schema, $queries, 'orob2b_product_unit_precision', 'oro_product_unit_precision');
        $extension->renameTable($schema, $queries, 'orob2b_product_variant_link', 'oro_product_variant_link');
        $extension->renameTable($schema, $queries, 'orob2b_product_image', 'oro_product_image');
        $extension->renameTable($schema, $queries, 'orob2b_product_image_type', 'oro_product_image_type');

        // rename indexes
        $schema->getTable('orob2b_product')->dropIndex('idx_orob2b_product_sku');
        $schema->getTable('orob2b_product')->dropIndex('idx_orob2b_product_created_at');
        $schema->getTable('orob2b_product')->dropIndex('idx_orob2b_product_updated_at');
        $schema->getTable('orob2b_product_unit_precision')
            ->dropIndex('product_unit_precision__product_id__unit_code__uidx');

        $extension->addIndex($schema, $queries, 'oro_product', ['sku'], 'idx_oro_product_sku');
        $extension->addIndex($schema, $queries, 'oro_product', ['created_at'], 'idx_oro_product_created_at');
        $extension->addIndex($schema, $queries, 'oro_product', ['updated_at'], 'idx_oro_product_updated_at');
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_product_unit_precision',
            ['product_id', 'unit_code'],
            'uidx_oro_product_unit_precision'
        );

        // system configuration
        $queries->addPostQuery(new RenameConfigSectionQuery('orob2b_product', 'oro_product'));
    }

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
    public function getOrder()
    {
        return 10;
    }
}
