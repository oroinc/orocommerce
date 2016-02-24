<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class ActionsDataProvider implements DataProviderInterface
{
    /**
     * @var  ActionManager
     */
    protected $actionManager;

    /**
     * @var  RestrictHelper
     */
    protected $restrictHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ActionManager $actionManager
     * @param RestrictHelper $restrictHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ActionManager $actionManager,
        RestrictHelper $restrictHelper,
        TranslatorInterface $translator
    ) {
        $this->actionManager = $actionManager;
        $this->restrictHelper = $restrictHelper;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        // TODO: add real ID here
        return 'some_id';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        // TODO: get group from block options
        $group = null;
        $actions = $this->restrictHelper->restrictActionsByGroup($this->actionManager->getActions(), $group);

        $data = [];
        foreach ($actions as $action) {
            if (!$action->getDefinition()->isEnabled()) {
                continue;
            }

            $definition = $action->getDefinition();

            $frontendOptions = $definition->getFrontendOptions();
            $buttonOptions = $definition->getButtonOptions();
            if (!empty($frontendOptions['title'])) {
                $title = $frontendOptions['title'];
            } else {
                $title = $definition->getLabel();
            }
            // TODO: Use icons mapping service here
            $icon = !empty($buttonOptions['icon']) ? $buttonOptions['icon'] : '';

            $data[] = [
                'name' => $definition->getName(),
                'label' => $this->translator->trans($definition->getLabel()),
                'title' => $this->translator->trans($title),
                'hasForm' => $action->hasForm(),
                'showDialog' => !empty($frontendOptions['show_dialog']),
                'icon' =>  $icon,
                'buttonOptions' => $buttonOptions,
                'frontendOptions' => $frontendOptions
            ];
        }

        return $data;
    }
}
