<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_31;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Turn field acl supported for Product.
 */
class TurnFieldAclSupportForProducts implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                Product::class,
                'security',
                'field_acl_supported',
                true
            )
        );
    }
}
