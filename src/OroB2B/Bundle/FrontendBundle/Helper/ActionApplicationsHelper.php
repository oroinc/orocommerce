<?php

namespace OroB2B\Bundle\FrontendBundle\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class ActionApplicationsHelper extends ApplicationsHelper
{
    const FRONTEND = 'frontend';

    /**
     * @return string|null
     */
    public function getCurrentApplication()
    {
        if (null !== ($app = parent::getCurrentApplication())) {
            return $app;
        }

        return $this->isFrontend() ? self::FRONTEND : null;
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
        return $this->isFrontend() ? 'orob2b_api_frontend_action_execute' : parent::getExecutionRoute();
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
