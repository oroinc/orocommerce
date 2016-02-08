<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Oro\Bundle\ActionBundle\Twig\ActionExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Component\Layout\Block\Type\AbstractType;

use OroB2B\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ActionLineButtonsType extends AbstractType
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

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ActionApplicationsHelper $applicationsHelper
     * @param RequestStack $requestStack
     * @param ActionExtension $actionExtension
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ActionApplicationsHelper $applicationsHelper,
        RequestStack $requestStack,
        ActionExtension $actionExtension
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationsHelper;
        $this->requestStack = $requestStack;
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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array_merge(
                [
                    'actions' => $this->actionManager->getActions(),
                    'context' => $this->contextHelper->getContext(),
                    'actionData' => $this->contextHelper->getActionData(),
                    'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                    'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                    'fromUrl' => $this->requestStack->getCurrentRequest()->get('fromUrl')
                ],
                $this->actionExtension->getWidgetParameters($this->contextHelper->getContext())
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['actions'] = $options['actions'];
        $view->vars['context'] = $options['context'];
        $view->vars['actionData'] = $options['actionData'];
        $view->vars['dialogRoute'] = $options['dialogRoute'];
        $view->vars['executionRoute'] = $options['executionRoute'];
        $view->vars['fromUrl'] = $options['fromUrl'];
        
    }
}
