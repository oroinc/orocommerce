<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_35;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDefaultProductVariantField implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addOroDefaultVariantField($schema);
    }

    private function addOroDefaultVariantField(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');

        if ($table->hasColumn('default_variant_id')) {
            return;
        }

        $table->addColumn('default_variant_id', 'integer', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['default_variant_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
