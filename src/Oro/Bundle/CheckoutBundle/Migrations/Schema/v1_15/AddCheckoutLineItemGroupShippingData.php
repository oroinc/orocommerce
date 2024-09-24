<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCheckoutLineItemGroupShippingData implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_checkout')
            ->addColumn('line_item_group_shipping_data', 'json', ['notnull' => false, 'comment' => '(DC2Type:json)']);
    }
}
