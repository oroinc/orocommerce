<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

/**
 * Defines default options for moving line items mass action.
 */
class MoveProductsMassAction extends AjaxMassAction
{
    /**
     * {@inheritdoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_shopping_list.mass_action.move_products_handler';
        }

        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'move-products-mass';
        }

        if (!isset($options['route'])) {
            $options['route'] = 'oro_shopping_list_frontend_move_mass_action';
        }

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (!isset($options['frontend_handle'])) {
            $options['frontend_handle'] = 'dialog';
        }

        if (!isset($options['selectedElement'])) {
            $options['selectedElement'] = 'input[name="selected"]:checked';
        }

        $options['confirmation'] = false;

        return parent::setOptions($options);
    }
}
