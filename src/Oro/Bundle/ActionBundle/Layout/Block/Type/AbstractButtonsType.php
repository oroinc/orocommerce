<?php

namespace Oro\Bundle\ActionBundle\Layout\Block\Type;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\RestrictHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Component\Layout\Block\Type\AbstractContainerType;

abstract class AbstractButtonsType extends AbstractContainerType
{
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
    abstract public function getName();

    /**
     * @param array $options
     * @return array
     */
    protected function setActionParameters(array $options)
    {
        /** @var Action[] $actions */
        $options['context']['entity'] = $options['entity'];
        $options['context'] = $this->contextHelper->getActionParameters($options['context']);

        return $options;
    }

    /**
     * @param array $options
     * @return \Oro\Bundle\ActionBundle\Model\Action[]
     */
    protected function getActions(array $options)
    {
        $actions = $options['actions'];
        if (array_key_exists('group', $options)) {
            $actions = $this->restrictHelper->restrictActionsByGroup($actions, $options['group']);

            return $actions;
        }

        return $actions;
    }

    /**
     * @param array $options
     * @param Action $action
     * @return array
     */
    protected function getParams(array $options, Action $action)
    {
        $actionName = $action->getName();
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

        return [
            'label' => $definition->getLabel(),
            'path' => $path,
            'actionUrl' => $actionUrl,
            'buttonOptions' => $definition->getButtonOptions(),
            'frontendOptions' => $definition->getFrontendOptions()
        ];
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
}
