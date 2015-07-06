<?php

namespace OroB2B\Bundle\CustomerBundle\EventListener;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();

        $request->attributes->set('_fullRedirect', true);
    }
}
