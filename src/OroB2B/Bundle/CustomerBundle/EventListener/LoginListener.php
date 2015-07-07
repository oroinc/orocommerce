<?php

namespace OroB2B\Bundle\CustomerBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class LoginListener
{
    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if ($event->getAuthenticationToken()->getUser() instanceof AccountUser) {
            $request = $event->getRequest();

            $request->attributes->set('_fullRedirect', true);
        }
    }
}
