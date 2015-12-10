<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class ActionExtension extends AbstractExtension
{
    /** @var ActionManager */
    protected $actionManager;

    /**
     * @param TranslatorInterface $translator
     * @param ActionManager $actionManager
     */
    public function __construct(TranslatorInterface $translator, ActionManager $actionManager)
    {
        $this->translator = $translator;
        $this->actionManager = $actionManager;
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
        $actionConfigurationConfig = $config->offsetExists('[action_configuration]')
            ? $config->offsetGet('[action_configuration]') : [];

        foreach ($actions as $action) {
            $actionConfigurationConfig[$action->getName()] = false;
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
        $config->offsetSet('[action_configuration]', $actionConfigurationConfig);

        return true;
    }
}
