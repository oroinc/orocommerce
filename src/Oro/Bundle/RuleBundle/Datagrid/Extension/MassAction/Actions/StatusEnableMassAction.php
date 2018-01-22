<?php

namespace Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\AbstractMassAction;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function getAllowedRequestTypes()
    {
        return [Request::METHOD_POST];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return Request::METHOD_POST;
    }
}
