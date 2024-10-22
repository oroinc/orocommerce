<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_34_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class AddKitShippingCalculationMethod implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_product')) {
            $table = $schema->getTable('oro_product');

            if (!$table->hasColumn('kit_shipping_calculation_method')) {
                $table->addColumn('kit_shipping_calculation_method', 'string', ['length' => 32, 'notnull' => false]);
            }

            $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
                'UPDATE oro_product SET kit_shipping_calculation_method = :method ' .
                'WHERE type = :type AND kit_shipping_calculation_method = NULL',
                [
                    'method' => Product::KIT_SHIPPING_ALL,
                    'type' => Product::TYPE_KIT
                ],
                [
                    'method' => Types::STRING,
                    'type' => Types::STRING
                ]
            ));
        }
    }
}
