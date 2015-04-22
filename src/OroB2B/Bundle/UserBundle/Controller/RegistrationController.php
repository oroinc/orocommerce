<?php

namespace OroB2B\Bundle\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;

class RegistrationController extends BaseController
{
    /**
     * {@inheritDoc}
     */
    public function registerAction()
    {
        $allowRegistration = $this->container
            ->get('oro_config.fake_manager')
            ->get('orob2b_user.allow_frontend_registration');

        if (!$allowRegistration) {
            return $this->container
                ->get('templating')
                ->renderResponse('OroB2BUserBundle:Registration:register_disallow.html.twig');
        }

        return parent::registerAction();
    }
}
