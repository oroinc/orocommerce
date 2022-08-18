<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The provider of the scope and the scope criteria for the current storefront request.
 */
class RequestWebContentScopeProvider
{
    private const REQUEST_SCOPE_ATTRIBUTE    = '_web_content_scope';
    private const REQUEST_SCOPES_ATTRIBUTE = '_web_content_scopes';
    private const REQUEST_CRITERIA_ATTRIBUTE = '_web_content_criteria';

    /** @var RequestStack */
    private $requestStack;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ScopeManager */
    private $scopeManager;

    /** @var MatchedUrlDecisionMaker */
    private $matchedUrlDecisionMaker;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ScopeManager $scopeManager,
        MatchedUrlDecisionMaker $matchedUrlDecisionMaker
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->scopeManager = $scopeManager;
        $this->matchedUrlDecisionMaker = $matchedUrlDecisionMaker;
    }

    public function getScope(): ?Scope
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_SCOPE_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_SCOPE_ATTRIBUTE);
        }

        $scope = null;
        $criteria = $this->getRequestScopeCriteria($request);
        if (null !== $criteria) {
            $scope = $this->getSlugRepository()->findMostSuitableUsedScope($criteria);
        }
        $request->attributes->set(self::REQUEST_SCOPE_ATTRIBUTE, $scope);

        return $scope;
    }

    public function getScopes(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_SCOPES_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_SCOPES_ATTRIBUTE);
        }

        $scopes = [];
        $criteria = $this->getRequestScopeCriteria($request);
        if (null !== $criteria) {
            $scopes = $this->getSlugRepository()->findMostSuitableUsedScopes($criteria);
        }
        $request->attributes->set(self::REQUEST_SCOPES_ATTRIBUTE, $scopes);

        return $scopes;
    }

    public function getScopeCriteria(): ?ScopeCriteria
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        return $this->getRequestScopeCriteria($request);
    }

    private function getRequestScopeCriteria(Request $request): ?ScopeCriteria
    {
        if ($request->attributes->has(self::REQUEST_CRITERIA_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_CRITERIA_ATTRIBUTE);
        }

        $criteria = null;
        if ($this->matchedUrlDecisionMaker->matches($request->getPathInfo())) {
            $criteria = $this->scopeManager->getCriteria('web_content');
        }
        $request->attributes->set(self::REQUEST_CRITERIA_ATTRIBUTE, $criteria);

        return $criteria;
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getRepository(Slug::class);
    }
}
