<?php

/*
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\AccessListener;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * Firewall uses a FirewallMap to register security listeners for the given
 * request.
 *
 * It allows for different security strategies within the same application
 * (a Basic authentication for the /api, and a web based authentication for
 * everything else for instance).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Firewall
{
    /**
     * @var FirewallMapInterface
     */
    private $map;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \SplObjectStorage
     */
    private $exceptionListeners;

    /**
     * @var ListenerInterface
     */
    private $accessListener;

    /**
     * Constructor.
     *
     * @param FirewallMapInterface     $map        A FirewallMapInterface instance
     * @param EventDispatcherInterface $dispatcher An EventDispatcherInterface instance
     */
    public function __construct(FirewallMapInterface $map, EventDispatcherInterface $dispatcher)
    {
        $this->map = $map;
        $this->dispatcher = $dispatcher;
        $this->exceptionListeners = new \SplObjectStorage();
    }

    /**
     * Handles security.
     *
     * @param GetResponseEvent $event An GetResponseEvent instance
     */
    public function onKernelRequestAuthenticate(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        // register listeners for this firewall
        /** @var ListenerInterface[] $listeners */
        /** @var ExceptionListener $exceptionListener */
        list($listeners, $exceptionListener) = $this->map->getListeners($event->getRequest());
        if (null !== $exceptionListener) {
            $this->exceptionListeners[$event->getRequest()] = $exceptionListener;
            $exceptionListener->register($this->dispatcher);
        }

        // Move AccessListener to be executed later after RouterListener
        $this->accessListener = null;
        if ($listeners && $listeners[count($listeners) - 1] instanceof AccessListener) {
            $this->accessListener = array_pop($listeners);
        }

        // initiate the listener chain
        foreach ($listeners as $listener) {
            $listener->handle($event);

            if ($event->hasResponse()) {
                break;
            }
        }
    }

    /**
     * Handles security.
     *
     * @param GetResponseEvent $event An GetResponseEvent instance
     */
    public function onKernelRequestAuthorize(GetResponseEvent $event)
    {
        if ($event->hasResponse() || !$event->isMasterRequest()) {
            return;
        }

        if ($this->accessListener) {
            $this->accessListener->handle($event);
        }
    }

    /**
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        $request = $event->getRequest();

        if (isset($this->exceptionListeners[$request])) {
            $this->exceptionListeners[$request]->unregister($this->dispatcher);
            unset($this->exceptionListeners[$request]);
        }
    }
}
