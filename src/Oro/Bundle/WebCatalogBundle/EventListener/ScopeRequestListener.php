<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ScopeRequestListener
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var SlugRepository
     */
    private $slugRepository;

    /**
     * @param ScopeManager $scopeManager
     * @param SlugRepository $slugRepository
     */
    public function __construct(ScopeManager $scopeManager, SlugRepository $slugRepository)
    {
        $this->scopeManager = $scopeManager;
        $this->slugRepository = $slugRepository;
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
        if ($scope && $this->slugRepository->isScopeAttachedToSlug($scope)) {
            $request->attributes->set('_web_content_scope', $scope);
        }
    }
}
