<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class ActionExtension extends AbstractExtension
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ActionManager */
    protected $actionManager;

    /** @var ActionPermissionProvider */
    protected $actionPermissionProvider;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param TranslatorInterface $translator
     * @param ActionManager $actionManager
     * @param ActionPermissionProvider $actionPermissionProvider
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        ActionManager $actionManager,
        ActionPermissionProvider $actionPermissionProvider,
        ContextHelper $contextHelper
    ) {
        $this->translator = $translator;
        $this->actionManager = $actionManager;
        $this->actionPermissionProvider = $actionPermissionProvider;
        $this->contextHelper = $contextHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $actions = $this->actionManager->getActionsForDatagrid($config->getName());
        if (0 === count($actions)) {
            return false;
        }
        $actionsConfig = $config->offsetGet('actions');

        foreach ($actions as $action) {
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
                    'entityClass' => $config->offsetGetByPath('[extended_entity_name]', false),
                    'showDialog' => $action->hasForm(),
                    'dialogOptions' => [
                        'title' => $this->translator->trans($action->getDefinition()->getLabel()),
                        'dialogOptions' => !empty($frontendOptions['dialog_options'])
                        ? $frontendOptions['dialog_options']
                        : [],
                    ]
                ]
            ];
        }
        $config->offsetSet('actions', $actionsConfig);
//        $actionConfigurationConfig = $config->offsetExists('action_configuration')
//            ? $config->offsetGet('action_configuration') : [];
        $actionConfigurationConfig = [$this, 'getActionsPermissions'];

        $config->offsetSet('action_configuration', $actionConfigurationConfig);

        return true;
    }


    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $actions = $this->actionManager->getActionsForDatagrid($config->getName());
        if (0 === count($actions)) {
            return;
        }
        /** @var ResultRecord[] $rows */
        $rows = $result->offsetGetByPath('[data]');
        $rows = is_array($rows) ? $rows : [];
        foreach ($rows as &$record) {
            $context = [
                'entityId' => $record->getValue('id'),
                'entityClass' => $config->offsetGetByPath('[extended_entity_name]', false),
            ];
            $actionContext = $this->contextHelper->getActionContext($context);
            $actionsList = [];
            foreach ($actions as $action) {
                $actionsList[$action->getName()] = $action->isAllowed($actionContext);
            }
            $record->addData(['actions' => $actionsList]);
        }
        unset($record);

        // set results
        $result->offsetSet('data', $rows);
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getActionsPermissions(ResultRecordInterface $record)
    {
        $actions = $record->getValue('actions');

        return $actions ? : [];
    }
}
