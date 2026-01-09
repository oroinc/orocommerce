<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

/**
 * Configures default options for the "add products to shopping list" mass action in datagrids.
 *
 * This mass action allows users to select multiple products from a datagrid and add them to a shopping list.
 * It sets up the frontend type, handler, route, and other configuration options required for the AJAX-based mass action
 * to function properly.
 */
class AddProductsMassAction extends AjaxMassAction
{
    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'add-products-mass';
        }

        if (!isset($options['handler'])) {
            $options['handler'] = 'oro_shopping_list.mass_action.add_products_handler';
        }

        if (!isset($options['route'])) {
            $options['route'] = 'oro_shopping_list_add_products_massaction';
        }

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (!isset($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['confirmation'] = false;

        return parent::setOptions($options);
    }
}
