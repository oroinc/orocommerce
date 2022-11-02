<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\InsertEntityConfigIndexFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class MakeVisibleDefaultProductAttributes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->makeAttributesVisible($queries);
        $this->makeSeoAttributeGroupInvisible($queries);
    }

    public function makeSeoAttributeGroupInvisible(QueryBag $queries)
    {
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_attribute_group SET is_visible = :visible WHERE code = :code',
            [
                'code' => 'seo',
                'visible' => '0'
            ],
            [
                'code' => Types::STRING,
                'visible' => Types::STRING
            ]
        ));
    }

    public function makeAttributesVisible(QueryBag $queries)
    {
        $defaultProductFields = [
            'sku' => true,
            'names' => true,
            'descriptions' => true,
            'shortDescriptions' => true,
            'images' => true,
            'inventory_status' => true,
            'productPriceAttributesPrices' => true,
            'metaKeywords' => false,
            'metaDescriptions' => false
        ];

        foreach ($defaultProductFields as $field => $visible) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    Product::class,
                    $field,
                    'attribute',
                    'visible',
                    $visible
                )
            );

            $queries->addPostQuery(
                new InsertEntityConfigIndexFieldValueQuery(
                    Product::class,
                    $field,
                    'attribute',
                    'visible',
                    $visible
                )
            );
        }
    }
}
