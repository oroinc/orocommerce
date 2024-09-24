<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;

/**
 * Makes "Open Orders" as default view for back-office order grid.
 */
class OrdersViewList extends AbstractViewsList
{
    #[\Override]
    protected function getViewsList(): array
    {
        $view = new View(
            'oro_order.open_orders',
            [
                'internal_status' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => ['order_internal_status.open']
                ]
            ]
        );
        $view->setLabel($this->translator->trans('oro.order.datagrid.view.open_orders'))
            ->setDefault(true);

        return [$view];
    }
}
