<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigIndexFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class AddDefaultProductAttributes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addDefaultProductAttributes($queries);
    }

    public function addDefaultProductAttributes(QueryBag $queries)
    {
        $defaultProductFields = [
            'sku',
            'names',
            'descriptions',
            'shortDescriptions',
            'images',
        ];

        foreach ($defaultProductFields as $field) {
            $queries->addPostQuery(
                new UpdateEntityConfigIndexFieldValueQuery(
                    Product::class,
                    $field,
                    'attribute',
                    'is_attribute',
                    true
                )
            );

            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    Product::class,
                    $field,
                    'attribute',
                    'is_attribute',
                    true
                )
            );
        }
    }
}
