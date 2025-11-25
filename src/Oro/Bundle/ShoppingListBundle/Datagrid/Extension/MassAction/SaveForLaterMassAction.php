<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

/**
 * Defines default options for save-for-later mass action.
 */
class SaveForLaterMassAction extends AjaxMassAction
{
    #[\Override]
    public function setOptions(ActionConfiguration $options): ActionInterface
    {
        if (!isset($options['handler'])) {
            $options['handler'] = 'oro_shopping_list.mass_action.save_for_later_handler';
        }

        if (!isset($options['frontend_type'])) {
            $options['frontend_type'] = 'save-for-later-mass';
        }

        if (!isset($options['route'])) {
            $options['route'] = 'oro_frontend_datagrid_mass_action';
        }

        if (!isset($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        return parent::setOptions($options);
    }
}
