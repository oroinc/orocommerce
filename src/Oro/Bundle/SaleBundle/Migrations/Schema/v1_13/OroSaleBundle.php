<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @inheritDoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameShippingEstimateAmountColumn($schema, $queries);
        $this->renameShippingEstimateCurrencyColumn($schema, $queries);
        $this->addOverriddenShippingCostColumn($schema);
        $this->addShippingMethodColumns($schema);
        $this->addAllowUnlistedAndLockedColumns($schema);
    }

    private function addAllowUnlistedAndLockedColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addColumn('shipping_method_locked', 'boolean', ['default' => false]);
        $table->addColumn('allow_unlisted_shipping_method', 'boolean', ['default' => false]);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOverriddenShippingCostColumn(Schema $schema)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addColumn('override_shipping_cost_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
    }

    /**
     * Add shipping_method, shipping_method_type columns
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addShippingMethodColumns(Schema $schema)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function renameShippingEstimateAmountColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'shipping_estimate_amount',
            'estimated_shipping_cost_amount'
        );
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function renameShippingEstimateCurrencyColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'shipping_estimate_currency',
            'currency'
        );
    }
}
