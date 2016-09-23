<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // notes
        $notes = $schema->getTable('oro_note');
        $notes->removeForeignKey('FK_BA066CE1C97633D');
        $extension->renameColumn($schema, $queries, $notes, 'category_ae9f2afb_id', 'category_670316e_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_catalog_category',
            ['category_670316e_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CatalogBundle\Entity\Category',
            'category_ae9f2afb',
            'category_670316e',
            RelationType::MANY_TO_ONE
        ));

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_catalog_category', 'oro_catalog_category');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_category_title', 'oro_catalog_category_title');
        $extension->renameTable($schema, $queries, 'orob2b_category_to_product', 'oro_category_to_product');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_cat_short_desc', 'oro_catalog_cat_short_desc');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_cat_long_desc', 'oro_catalog_cat_long_desc');
        $extension->renameTable($schema, $queries, 'orob2b_category_def_prod_opts', 'oro_category_def_prod_opts');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
