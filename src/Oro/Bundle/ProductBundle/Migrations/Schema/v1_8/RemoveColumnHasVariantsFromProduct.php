<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveColumnHasVariantsFromProduct implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_product')
            ->dropColumn('has_variants');

        $queries->addPostQuery(
            new RemoveFieldQuery('Oro\Bundle\ProductBundle\Entity\Product', 'hasVariants')
        );
    }
}
