<?php

namespace Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;

class StatusEnableMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'entity_name', 'data_identifier'];

    /**
     * {@inheritDoc}
     */
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = 'oro_payment.mass_action.status_handler';
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = '';
        }

        if (empty($options['route'])) {
            $options['route'] = 'oro_payment_methods_configs_massaction';
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['enable'] = true;

        return parent::setOptions($options);
    }
}
