<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Listener for modification of `order-line-items-grid` and `order-line-items-grid-frontend` datagrids
 */
class OrderLineItemGridListener
{
    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    public function __construct(TaxationSettingsProvider $taxationSettingsProvider)
    {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * Listens for events:
     *      `oro_datagrid.datagrid.build.before.order-line-items-grid`
     *      `oro_datagrid.datagrid.build.before.order-line-items-grid-frontend`
     * and adds columns with
     *      Row Total Discount Amount
     *      Row Total After Discount Include Tax (if Taxes are enabled)
     *      Row Total After Discount Exclude Tax (if Taxes are enabled)
     *      Row Total After Discount (if Taxes are disabled)
     * data to the grid
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $configuration = $event->getConfig();
        $this->addSelect($configuration);
        $this->addColumns($configuration);
    }

    protected function addSelect(DatagridConfiguration $config)
    {
        $rootAlias = $config->getOrmQuery()->getRootAlias();
        $config->getOrmQuery()->addSelect(
            '(SELECT SUM(discount.amount) FROM Oro\\Bundle\\PromotionBundle\\Entity\\AppliedDiscount AS discount '
            . 'WHERE discount.lineItem = ' . $rootAlias . '.id) AS discountAmount'
        );
    }

    protected function addColumns(DatagridConfiguration $datagridConfiguration)
    {
        $datagridConfiguration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalDiscountAmount'),
            [
                'label' => 'oro.order.view.order_line_item.row_total_discount_amount.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'discountAmount',
                'template' => '@OroPromotion/Datagrid/Order/rowTotalDiscountAmount.html.twig',
                'renderable' => false
            ]
        );

        if ($this->isTaxesEnabled()) {
            $datagridConfiguration->offsetSetByPath(
                sprintf('[columns][%s]', 'rowTotalAfterDiscountIncludingTax'),
                [
                    'label' => 'oro.order.view.order_line_item.row_total_after_discount_including_tax.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'data_name' => 'discountAmount',
                    'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscountIncludingTax.html.twig',
                    'renderable' => false
                ]
            );
            $datagridConfiguration->offsetSetByPath(
                sprintf('[columns][%s]', 'rowTotalAfterDiscountExcludingTax'),
                [
                    'label' => 'oro.order.view.order_line_item.row_total_after_discount_excluding_tax.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'data_name' => 'discountAmount',
                    'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscountExcludingTax.html.twig',
                    'renderable' => false
                ]
            );
        } else {
            $datagridConfiguration->offsetSetByPath(
                sprintf('[columns][%s]', 'rowTotalAfterDiscount'),
                [
                    'label' => 'oro.order.view.order_line_item.row_total_after_discount.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'data_name' => 'discountAmount',
                    'template' => '@OroPromotion/Datagrid/Order/rowTotalAfterDiscount.html.twig',
                    'renderable' => false
                ]
            );
        }
    }

    /**
     * Check if Taxes are enabled
     */
    protected function isTaxesEnabled(): bool
    {
        return $this->taxationSettingsProvider->isEnabled();
    }
}
