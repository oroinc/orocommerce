<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\MoveEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class OroProductBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new MoveEntityConfigFieldValueQuery(
                Product::class,
                null,
                'attribute',
                'visible',
                'frontend',
                'is_displayable'
            )
        );
    }
}
