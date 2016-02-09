<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Twig\ActionExtension;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;

use OroB2B\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ActionLineButtonsType extends AbstractContainerType
{
    const NAME = 'action_line_buttons';

    /** @var  ActionManager */
    protected $actionManager;

    /** @var  ContextHelper */
    protected $contextHelper;

    /** @var  ActionApplicationsHelper */
    protected $applicationsHelper;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  ActionExtension */
    protected $actionExtension;

    /** @var  UrlGeneratorInterface */
    protected $router;

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ActionApplicationsHelper $applicationsHelper
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ActionApplicationsHelper $applicationsHelper,
        RequestStack $requestStack,
        UrlGeneratorInterface $router
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockBuilderInterface $builder
     * @param array $options
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
        /** @var Action[] $actions */
        $actions = $options['actions'];

        if (array_key_exists('group', $options)) {
            $groups = $options['group'] === null ? null : (array)$options['group'];
            $actions = $this->restrictActions($actions, $groups);
        }

        foreach ($actions as $actionName => $action) {
            $definition = $action->getDefinition();
            $path = $this->router->generate(
                $options['executionRoute'] ?: 'oro_api_action_execute_actions',
                array_merge($options['context'], ['actionName' => $action->getName()])
            );
            $actionUrl = null;
            if ($action->hasForm()) {
                $actionUrl = $this->router->generate(
                    $options['dialogRoute'] ?: 'oro_action_widget_form',
                    array_merge(
                        $options['context'],
                        ['actionName' => $action->getName(), 'fromUrl' => $options['fromUrl']]
                    )
                );
            }

            $params = [
                'label' => $definition->getLabel(),
                'path' => $path,
                'actionUrl' => $actionUrl,
                'buttonOptions' => $definition->getButtonOptions(),
                'frontendOptions' => $definition->getFrontendOptions()
            ];

            $builder->getLayoutManipulator()->add(
                $actionName . '_button',
                $builder->getId(),
                ActionButtonType::NAME,
                [
                    'params' => $params,
                    'context' => $options['context'],
                    'fromUrl' => $options['fromUrl'],
                    'actionData' => $options['actionData']
                ]
            );
        }
    }

    /**
     * @param Action[] $actions
     * @param array|null $groups
     * @return Action[]
     */
    protected function restrictActions($actions, $groups)
    {
        $restrictedActions = [];
        foreach ($actions as $key => $action) {
            $buttonOptions = $action->getDefinition()->getButtonOptions();
            if (array_key_exists('group', $buttonOptions)) {
                if ($groups !== null && in_array($buttonOptions['group'], $groups)) {
                    $restrictedActions[$key] = $action;
                }
            } elseif ($groups === null) {
                $restrictedActions[$key] = $action;
            }
        }

        return $restrictedActions;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $request = $this->requestStack->getCurrentRequest();
        $request->attributes->set('route', $request->get('_route'));
        $resolver->setDefaults(
            [
                'actions' => $this->actionManager->getActions(),
                'context' => $this->contextHelper->getContext(),
                'actionData' => $this->contextHelper->getActionData(),
                'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                'fromUrl' => $this->requestStack->getCurrentRequest()->get('fromUrl')
            ]
        );
        $resolver->setOptional(['group']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
//        $view->vars['actions'] = $options['actions'];
//        $view->vars['context'] = $options['context'];
//        $view->vars['actionData'] = $options['actionData'];
//        $view->vars['dialogRoute'] = $options['dialogRoute'];
//        $view->vars['executionRoute'] = $options['executionRoute'];
//        $view->vars['fromUrl'] = $options['fromUrl'];
    }
}
