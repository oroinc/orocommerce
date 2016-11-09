<?php

namespace Oro\Bundle\FrontendBundle\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperTrait;
use Oro\Bundle\ActionBundle\Helper\RouteHelperTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class ActionApplicationsHelper implements ApplicationsHelperInterface
{
    use ApplicationsHelperTrait, RouteHelperTrait;

    const COMMERCE_APPLICATION = 'commerce';

    /** @var  ApplicationsHelper */
    protected $applicationsHelper;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param ApplicationsHelperInterface $applicationsHelper
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ApplicationsHelperInterface $applicationsHelper, TokenStorageInterface $tokenStorage)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication()
    {
        return $this->isFrontend() ? self::COMMERCE_APPLICATION : $this->applicationsHelper->getCurrentApplication();
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetRoute()
    {
        return $this->isFrontend() ? 'oro_frontend_action_widget_buttons' : $this->applicationsHelper->getWidgetRoute();
    }

    /**
     * {@inheritdoc}
     */
    public function getDialogRoute()
    {
        return $this->isFrontend() ? 'oro_frontend_action_widget_form' : $this->applicationsHelper->getDialogRoute();
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionRoute()
    {
        return $this->isFrontend() ? 'oro_frontend_action_operation_execute' :
            $this->applicationsHelper->getExecutionRoute();
    }

    /**
     * @return bool
     */
    protected function isFrontend()
    {
        $token = $this->tokenStorage->getToken();

        return $token && $token->getUser() instanceof AccountUser;
    }
}
