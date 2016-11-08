<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;
use Symfony\Component\Security\Http\FirewallMapInterface;

class FirewallFactory
{
    /**
     * @param FirewallMapInterface $map
     * @param EventDispatcherInterface $dispatcher
     * @return FrameworkFirewall
     */
    public function create(FirewallMapInterface $map, EventDispatcherInterface $dispatcher)
    {
        return new FrameworkFirewall($map, $dispatcher);
    }
}
