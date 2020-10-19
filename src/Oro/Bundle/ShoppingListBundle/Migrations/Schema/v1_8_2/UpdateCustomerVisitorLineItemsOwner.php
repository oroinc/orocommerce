<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_8_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateCustomerVisitorLineItemsOwner implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateCustomerVisitorLineItemsOwnerQuery());
    }
}
