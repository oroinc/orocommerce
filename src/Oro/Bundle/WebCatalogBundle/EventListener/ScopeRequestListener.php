<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Set used web catalog scope to request attribute _web_content_scope
 * Used in menu data provider.
 */
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
     * @var MatchedUrlDecisionMaker
     */
    private $matchedUrlDecisionMaker;

    /**
     * @param ScopeManager $scopeManager
     * @param SlugRepository $slugRepository
     * @param MatchedUrlDecisionMaker $matchedUrlDecisionMaker
     */
    public function __construct(
        ScopeManager $scopeManager,
        SlugRepository $slugRepository,
        MatchedUrlDecisionMaker $matchedUrlDecisionMaker
    ) {
        $this->scopeManager = $scopeManager;
        $this->slugRepository = $slugRepository;
        $this->matchedUrlDecisionMaker = $matchedUrlDecisionMaker;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->attributes->has('_web_content_scope')) {
            return;
        }
        if (!$this->matchedUrlDecisionMaker->matches($request->getPathInfo())) {
            return;
        }

        $criteria = $this->scopeManager->getCriteria('web_content');
        $scope = $this->slugRepository->findMostSuitableUsedScope($criteria);
        if ($scope) {
            $request->attributes->set('_web_content_scope', $scope);
        }
    }
}
