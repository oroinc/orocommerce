<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProductBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $productClass = 'Oro\Bundle\ProductBundle\Entity\Product';
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery($productClass, 'hasVariants', 'importexport', 'excluded', false)
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery($productClass, 'variantLinks', 'importexport', 'excluded', false)
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery($productClass, 'variantLinks', 'importexport', 'full', true)
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery($productClass, 'variantFields', 'importexport', 'excluded', false)
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                $productClass,
                'variantFields',
                'importexport',
                'process_as_scalar',
                true
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ProductBundle\Entity\ProductVariantLink',
                'product',
                'importexport',
                'order',
                10
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery($productClass, 'inventory_status', 'dataaudit', 'auditable', true)
        );
    }
}
