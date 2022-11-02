<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

class UpdateAclsForProductAndFamily implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateEntityConfigEntityValueQuery(
            Product::class,
            'security',
            'category',
            'catalog'
        ));

        $queries->addQuery(new UpdateEntityConfigEntityValueQuery(
            AttributeFamily::class,
            'security',
            'category',
            'catalog'
        ));
    }
}
