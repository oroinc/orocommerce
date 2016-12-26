<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ScopeRequestListener
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest() || $request->attributes->has('_web_content_scope')) {
            return;
        }

        $scope = $this->scopeManager->findMostSuitable('web_content');
        if ($scope) {
            $request->attributes->set('_web_content_scope', $scope);
        }
    }
}
