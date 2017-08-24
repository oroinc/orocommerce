<?php

namespace Oro\Bundle\OrderBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;

class OrdersViewList extends AbstractViewsList
{
    /**
     * {@inheritdoc}
     */
    protected function getViewsList()
    {
        $view = new View(
            'oro_order.open_orders',
            [
                'internalStatusName' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => ['open']
                ]
            ]
        );
        $view->setLabel($this->translator->trans('oro.order.datagrid.view.open_orders'))
            ->setDefault(true);

        return [$view];
    }
}
