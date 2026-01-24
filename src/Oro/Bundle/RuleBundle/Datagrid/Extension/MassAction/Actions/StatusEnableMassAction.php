<?php

namespace Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles bulk enable/disable actions for rules in datagrids.
 *
 * This mass action allows administrators to enable or disable multiple rules at once through the datagrid interface.
 * It extends the base mass action functionality and configures itself with a specific handler service, route,
 * and enabled state. The action automatically sets up the required options for processing bulk rule status changes
 * via AJAX POST requests, providing a seamless user experience for managing rule states in bulk operations.
 */
class StatusEnableMassAction extends AbstractMassAction
{
    /** @var array */
    protected $requiredOptions = ['handler', 'entity_name', 'data_identifier'];

    /**
     * @var string
     */
    private $handlerService;

    /**
     * @var string
     */
    private $route;

    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @param string $handlerService
     * @param string $route
     * @param bool   $isEnabled
     */
    public function __construct($handlerService, $route, $isEnabled)
    {
        $this->handlerService = $handlerService;
        $this->route = $route;
        $this->isEnabled = $isEnabled;

        parent::__construct();
    }

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (empty($options['handler'])) {
            $options['handler'] = $this->handlerService;
        }

        if (empty($options['frontend_type'])) {
            $options['frontend_type'] = '';
        }

        if (empty($options['route'])) {
            $options['route'] = $this->route;
        }

        if (empty($options['route_parameters'])) {
            $options['route_parameters'] = [];
        }

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        $options['enable'] = $this->isEnabled;

        return parent::setOptions($options);
    }

    #[\Override]
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }

    #[\Override]
    protected function getRequestType()
    {
        return Request::METHOD_POST;
    }
}
