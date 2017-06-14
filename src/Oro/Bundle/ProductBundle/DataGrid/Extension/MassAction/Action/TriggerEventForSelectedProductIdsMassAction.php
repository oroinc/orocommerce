<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

/**
 * Defines default options for triggering event for product ids mass action.
 */
class TriggerEventForSelectedProductIdsMassAction extends AjaxMassAction
{
    protected $requiredOptions = ['handler', 'event_name'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'trigger-event-for-selected-product-ids-mass';
        }

        if (!isset($options['handler'])) {
            $options['handler'] =
                'oro_product.datagrid.extension.mass_action.trigger_event_for_selected_product_ids_mass_action_handler';
        }

        if (!isset($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['confirmation'] = false;
        $options['reloadData'] = false;

        return parent::setOptions($options);
    }
}
