<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameShippingCostColumn($schema, $queries);
        $this->addOverriddenShippingCostColumn($schema);
    }

    protected function addOverriddenShippingCostColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_order');
        $table->addColumn('override_shipping_cost_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
    }

    protected function renameShippingCostColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order');
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'shipping_cost_amount',
            'estimated_shipping_cost_amount'
        );
    }
}
