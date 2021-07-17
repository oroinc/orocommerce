<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;

/**
 * Decorate default framework firewall, perform token initialization before routing to make user available there.
 * Perform after routing firewall checks for URL that are managed by slugs and redirect to login if required.
 */
class Firewall
{
    /** @var MatchedUrlDecisionMaker */
    private $matchedUrlDecisionMaker;

    /** @var SlugRequestFactoryInterface */
    private $slugRequestFactory;

    /** @var RequestContext */
    private $context;

    /** @var FrameworkFirewall */
    private $baseFirewall;

    /** @var bool */
    private $slugApplied = false;

    public function __construct(
        MatchedUrlDecisionMaker $matchedUrlDecisionMaker,
        SlugRequestFactoryInterface $slugRequestFactory,
        RequestContext $context = null
    ) {
        $this->matchedUrlDecisionMaker = $matchedUrlDecisionMaker;
        $this->slugRequestFactory = $slugRequestFactory;
        $this->context = $context;
    }

    /**
     * Sets alternative base firewall.
     */
    public function setFirewall(FrameworkFirewall $firewall)
    {
        $this->baseFirewall = $firewall;
    }

    /**
     * Initialize request context by current request, call default firewall behaviour.
     */
    public function onKernelRequestBeforeRouting(RequestEvent $event)
    {
        if (!$this->matchedUrlDecisionMaker->matches($event->getRequest()->getPathInfo())) {
            return;
        }

        if (null !== $this->context) {
            $this->context->fromRequest($event->getRequest());
        }

        if ($event->isMasterRequest()) {
            $this->slugApplied = false;
        }
        $this->baseFirewall->onKernelRequest($event);
    }

    /**
     * For Slugs perform additional authentication checks for detected route.
     */
    public function onKernelRequestAfterRouting(RequestEvent $event)
    {
        if ($this->matchedUrlDecisionMaker->matches($event->getRequest()->getPathInfo())) {
            $request = $event->getRequest();
            if ($event->isMasterRequest()
                && !$event->hasResponse()
                && $request->attributes->has('_resolved_slug_url')
            ) {
                $this->baseFirewall->onKernelFinishRequest(new FinishRequestEvent(
                    $event->getKernel(),
                    $event->getRequest(),
                    $event->getRequestType()
                ));

                $slugRequest = $this->createSlugRequest($request);
                $slugEvent = new RequestEvent($event->getKernel(), $slugRequest, $event->getRequestType());
                $this->baseFirewall->onKernelRequest($slugEvent);
                $this->slugRequestFactory->updateMainRequest($event->getRequest(), $slugEvent->getRequest());
                if ($slugEvent->hasResponse()) {
                    $event->setResponse($slugEvent->getResponse());
                }

                $this->slugApplied = true;
            }
        } else {
            $this->baseFirewall->onKernelRequest($event);
        }
    }

    /**
     * Unregister exception listeners.
     */
    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if ($this->slugApplied) {
            $this->baseFirewall->onKernelFinishRequest(new FinishRequestEvent(
                $event->getKernel(),
                $this->createSlugRequest($event->getRequest()),
                $event->getRequestType()
            ));
        } else {
            $this->baseFirewall->onKernelFinishRequest($event);
        }
    }

    /**
     * @param Request $request
     *
     * @return Request
     */
    protected function createSlugRequest(Request $request)
    {
        return $this->slugRequestFactory->createSlugRequest($request);
    }
}
