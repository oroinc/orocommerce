<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;
use Symfony\Component\Security\Http\FirewallMapInterface;

/**
 * Decorate default framework firewall, perform token initialization before routing to make user available there.
 * Perform after routing firewall checks for URL that are managed by slugs and redirect to login if required.
 */
class Firewall
{
    /**
     * @var FrameworkFirewall
     */
    private $baseFirewall;

    /**
     * @var bool
     */
    private $slugApplied = false;

    /**
     * @var RequestContext
     */
    private $context;

    /**
     * @param FirewallMapInterface $map
     * @param EventDispatcherInterface $dispatcher
     * @param RequestContext|null $context
     */
    public function __construct(
        FirewallMapInterface $map,
        EventDispatcherInterface $dispatcher,
        RequestContext $context = null
    ) {
        $this->baseFirewall = new FrameworkFirewall($map, $dispatcher);
        $this->context = $context;
    }

    /**
     * Initialize request context by current request, call default firewall behaviour.
     *
     * @param GetResponseEvent $event An GetResponseEvent instance
     */
    public function onKernelRequestBeforeRouting(GetResponseEvent $event)
    {
        if ($this->context) {
            $this->context->fromRequest($event->getRequest());
        }

        if ($event->isMasterRequest()) {
            $this->slugApplied = false;
        }
        $this->baseFirewall->onKernelRequest($event);
    }

    /**
     * For Slugs perform additional authentication checks for detected route.
     *
     * @param GetResponseEvent $event An GetResponseEvent instance
     */
    public function onKernelRequestAfterRouting(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($event->isMasterRequest() && !$event->hasResponse() && $request->attributes->has('_resolved_slug_url')) {
            $finishRequestEvent = new FinishRequestEvent(
                $event->getKernel(),
                $event->getRequest(),
                $event->getRequestType()
            );
            $this->baseFirewall->onKernelFinishRequest($finishRequestEvent);

            $newRequest = $this->createSlugRequest($request);
            $newEvent = new GetResponseEvent(
                $event->getKernel(),
                $newRequest,
                $event->getRequestType()
            );
            $this->baseFirewall->onKernelRequest($newEvent);
            if ($newEvent->hasResponse()) {
                $event->setResponse($newEvent->getResponse());
            }

            $this->slugApplied = true;
        }
    }

    /**
     * Unregister exception listeners.
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if ($this->slugApplied) {
            $finishRequestEvent = new FinishRequestEvent(
                $event->getKernel(),
                $this->createSlugRequest($event->getRequest()),
                $event->getRequestType()
            );
            $this->baseFirewall->onKernelFinishRequest($finishRequestEvent);
        } else {
            $this->baseFirewall->onKernelFinishRequest($event);
        }
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function createSlugRequest(Request $request)
    {
        $newRequest = Request::create(
            $request->attributes->get('_resolved_slug_url'),
            $request->getMethod(),
            $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
        $newRequest->setSession($request->getSession());
        $newRequest->setLocale($request->getLocale());
        $newRequest->setDefaultLocale($request->getDefaultLocale());

        return $newRequest;
    }
}
