<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;

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

    /** @var  ApplicationsHelper */
    protected $applicationsHelper;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  UrlGeneratorInterface */
    protected $router;

    /** @var  RestrictHelper */
    protected $restrictHelper;

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationsHelper
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $router
     * @param RestrictHelper $restrictHelper
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationsHelper,
        RequestStack $requestStack,
        UrlGeneratorInterface $router,
        RestrictHelper $restrictHelper

    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->restrictHelper = $restrictHelper;
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
        $options['context'] = $this->contextHelper->getActionParameters($options['context']);

        if (array_key_exists('group', $options)) {
            $actions = $this->restrictHelper->restrictActionsByGroup($actions, $options['group']);
        }

        foreach ($actions as $actionName => $action) {
            $definition = $action->getDefinition();
            $path = $this->router->generate(
                $options['executionRoute'],
                array_merge($options['context'], ['actionName' => $actionName])
            );
            $actionUrl = null;
            if ($action->hasForm()) {
                $actionUrl = $this->router->generate(
                    $options['dialogRoute'],
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
