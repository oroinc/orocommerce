<?php

namespace Oro\Bundle\FrontendBundle\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

class ActionApplicationsHelper extends ApplicationsHelper
{
    const COMMERCE_APPLICATION = 'commerce';

    /**
     * @return string|null
     */
    public function getCurrentApplication()
    {
        return $this->isFrontend() ? self::COMMERCE_APPLICATION : parent::getCurrentApplication();
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return $this->isFrontend() ? 'orob2b_frontend_action_widget_buttons' : parent::getWidgetRoute();
    }

    /**
     * @return string
     */
    public function getDialogRoute()
    {
        return $this->isFrontend() ? 'orob2b_frontend_action_widget_form' : parent::getDialogRoute();
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return $this->isFrontend() ? 'orob2b_frontend_action_operation_execute' : parent::getExecutionRoute();
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
