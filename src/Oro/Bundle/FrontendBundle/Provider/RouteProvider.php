<?php

namespace Oro\Bundle\FrontendBundle\Provider;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\ActionBundle\Provider\RouteProviderTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RouteProvider implements RouteProviderInterface
{
    use RouteProviderTrait;

    /** @var RouteProviderInterface */
    protected $routeProvider;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param RouteProviderInterface $routeProvider
     * @param TokenStorageInterface $tokenStorage
     * @param string $formDialogRoute
     * @param string $formPageRoute
     * @param string $executionRoute
     * @param string|null $widgetRoute
     */
    public function __construct(
        RouteProviderInterface $routeProvider,
        TokenStorageInterface $tokenStorage,
        $formDialogRoute,
        $formPageRoute,
        $executionRoute,
        $widgetRoute = null
    ) {
        $this->routeProvider = $routeProvider;
        $this->tokenStorage = $tokenStorage;
        $this->formDialogRoute = $formDialogRoute;
        $this->formPageRoute = $formPageRoute;
        $this->executionRoute = $executionRoute;
        $this->widgetRoute = $widgetRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetRoute()
    {
        return $this->isFrontend() ? $this->widgetRoute : $this->routeProvider->getWidgetRoute();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormDialogRoute()
    {
        return $this->isFrontend() ? $this->formDialogRoute : $this->routeProvider->getFormDialogRoute();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormPageRoute()
    {
        return $this->isFrontend() ? $this->formPageRoute : $this->routeProvider->getFormPageRoute();
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionRoute()
    {
        return $this->isFrontend() ? $this->executionRoute : $this->routeProvider->getExecutionRoute();
    }

    /**
     * @return bool
     */
    protected function isFrontend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof CustomerUser;
    }
}
