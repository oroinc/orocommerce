<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateRelatedProductsTable implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /** @var RenameExtension */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product_related_products');

        $table->removeForeignKey($this->getConstraintName($table, "related_product_id"));
        $table->removeForeignKey($this->getConstraintName($table, "product_id"));
        $table->dropIndex('idx_oro_product_related_products_related_product_id');
        $table->dropIndex('idx_oro_product_related_products_unique');

        $this->renameExtension->renameColumn($schema, $queries, $table, 'related_product_id', 'related_item_id');
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
        return 1;
    }
}
