<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateProductImageTypeTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product_image_type');

        $foreignKeys = $table->getForeignKeys();
        /** @var ForeignKeyConstraint $oldForeignKey */
        $oldForeignKey = reset($foreignKeys);
        $table->removeForeignKey($oldForeignKey->getName());

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_image'),
            ['product_image_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
