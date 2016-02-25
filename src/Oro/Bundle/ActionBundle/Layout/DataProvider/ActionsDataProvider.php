<?php

namespace Oro\Bundle\ActionBundle\Layout\DataProvider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Action;
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
        return 'oro_action';
    }

    /**
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
        if (strpos($var, 'getByGroup') === 0) {
            $groups = explode('And', str_replace('getByGroup', '', $var));
            foreach ($groups as &$group) {
                $group = preg_replace('/(?<=[a-zA-Z])(?=[A-Z])/', '_', $group);
                $groupNameParts =  array_map('strtolower', explode('_', $group));
                $group = implode('_', $groupNameParts);
            }

            return $this->getRestrictedActions($groups);
        } else {
            throw new \RuntimeException('Property ' . $var . ' is unknown');
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->getPreparedData($this->actionManager->getActions());
    }

    /**
     * @return array
     */
    public function getWithoutGroup()
    {
        $actions = $this->restrictHelper->restrictActionsByGroup($this->actionManager->getActions(), false);

        return $this->getPreparedData($actions);
    }

    /**
     * @param array $groups
     * @return array
     */
    protected function getRestrictedActions(array $groups)
    {
        $actions = $this->restrictHelper->restrictActionsByGroup($this->actionManager->getActions(), $groups);

        return $this->getPreparedData($actions);
    }

    /**
     * @param Action[] $actions
     * @return array
     */
    protected function getPreparedData(array $actions = [])
    {
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

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this;
    }
}
