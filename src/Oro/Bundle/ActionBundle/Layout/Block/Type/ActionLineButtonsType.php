<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Twig\ActionExtension;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;

use OroB2B\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

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
     * @param ActionExtension $actionExtension
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ActionApplicationsHelper $applicationsHelper,
        RequestStack $requestStack,
        UrlGeneratorInterface $router,
        ActionExtension $actionExtension
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->actionExtension = $actionExtension;
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
        $options['context']['entity'] = $options['entity'];
        $options['context'] = $this->actionExtension->getWidgetParameters($options['context']);

        if (array_key_exists('group', $options)) {
            $actions = $this->actionManager->restrictActionsByGroup($actions, $options['group']);
        }

        foreach ($actions as $actionName => $action) {
            $definition = $action->getDefinition();
            $path = $this->router->generate(
                $options['executionRoute'] ?: 'oro_api_action_execute_actions',
                array_merge($options['context'], ['actionName' => $actionName])
            );
            $actionUrl = null;
            if ($action->hasForm()) {
                $actionUrl = $this->router->generate(
                    $options['dialogRoute'] ?: 'oro_action_widget_form',
                    array_merge(
                        $options['context'],
                        ['actionName' => $actionName, 'fromUrl' => $options['fromUrl']]
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
                'fromUrl' => $request->get('fromUrl')
            ]
        );
        $resolver->setOptional(['group', 'ul_class']);
        $resolver->setRequired(['entity']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (array_key_exists('ul_class', $options)) {
            $view->vars['ul_class'] = $options['ul_class'];
        }
    }
}
