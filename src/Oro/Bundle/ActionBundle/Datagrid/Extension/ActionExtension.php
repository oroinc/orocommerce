<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class ActionExtension extends AbstractExtension
{
    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelper */
    protected $applicationHelper;

    /** @var array */
    protected $actionConfiguration = [];

    /** @var array */
    protected $datagridContext = [];

    /** @var Action[] */
    protected $actions = [];

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationHelper
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationHelper
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->actions = $this->actionManager->getActions($this->datagridContext, false);
        if (0 === count($this->actions)) {
            return false;
        }
        $this->processActionsConfig($config);
        $this->actionConfiguration = $config->offsetGetOr('action_configuration', []);
        $config->offsetSet('action_configuration', [$this, 'getActionsPermissions']);

        return true;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getActionsPermissions(ResultRecordInterface $record)
    {
        $actionsOld = [];
        // process own permissions of the datagrid
        if ($this->actionConfiguration && is_callable($this->actionConfiguration)) {
            $actionsOld = call_user_func($this->actionConfiguration, $record);

            $actionsOld = is_array($actionsOld) ? $actionsOld : [];
        };

        $context = [
            'entityId' => $record->getValue('id'),
            'entityClass' => $this->datagridContext['entityClass'],
        ];
        $actionData = $this->contextHelper->getActionData($context);
        $actionsNew = [];
        foreach ($this->actions as $action) {
            $actionsNew[$action->getName()] = $action->isAllowed($actionData);
        }

        return array_merge($actionsOld, $actionsNew);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr('actions', []);

        foreach ($this->actions as $action) {
            $frontendOptions = $action->getDefinition()->getFrontendOptions();
            $actionsConfig[$action->getName()] = [
                'type' => 'action-widget',
                'label' => $action->getDefinition()->getLabel(),
                'rowAction' => false,
                'link' => '#',
                'icon' => !empty($frontendOptions['icon'])
                    ? str_ireplace('icon-', '', $frontendOptions['icon'])
                    : 'edit',
                'options' => [
                    'actionName' => $action->getName(),
                    'entityClass' => $this->datagridContext['entityClass'],
                    'datagrid' => $this->datagridContext['datagrid'],
                    'datagridConfirm' =>  !empty($frontendOptions['datagrid_confirm'])
                        ? $frontendOptions['datagrid_confirm']
                        : '',
                    'showDialog' => $action->hasForm(),
                    'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                    'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                    'dialogOptions' => [
                        'title' => $action->getDefinition()->getLabel(),
                        'dialogOptions' => !empty($frontendOptions['dialog_options'])
                            ? $frontendOptions['dialog_options']
                            : [],
                    ]
                ]
            ];
        }

        $config->offsetSet('actions', $actionsConfig);
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        $entityClass = $config->offsetGetByPath('[extended_entity_name]');

        return [
            'entityClass' => $entityClass ? : $config->offsetGetByPath('[entity_name]'),
            'datagrid' => $config->offsetGetByPath('[name]'),
        ];
    }
}
