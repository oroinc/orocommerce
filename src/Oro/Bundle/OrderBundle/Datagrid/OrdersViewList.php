<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

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
                    'value' => ExtendHelper::mapToEnumOptionIds(Order::INTERNAL_STATUS_CODE, [
                        OrderStatusesProviderInterface::INTERNAL_STATUS_PENDING,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_PROCESSING
                    ])
                ]
            ]
        );
        $view->setLabel($this->translator->trans('oro.order.datagrid.view.open_orders'));
        $view->setDefault(true);

        return [$view];
    }
}
