<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Provides methods to find a slug entity.
 */
class SlugEntityFinder
{
    private SlugRepository $slugRepository;
    private ScopeManager $scopeManager;
    private ?ScopeCriteria $scopeCriteria = null;

    public function __construct(SlugRepository $slugRepository, ScopeManager $scopeManager)
    {
        $this->slugRepository = $slugRepository;
        $this->scopeManager = $scopeManager;
    }

    /**
     * Finds a slug entity by the given URL.
     */
    public function findSlugEntityByUrl(string $url): ?Slug
    {
        return $this->slugRepository->getSlugByUrlAndScopeCriteria($url, $this->getScopeCriteria());
    }

    /**
     * Finds a slug entity by the given slug prototype.
     */
    public function findSlugEntityBySlugPrototype(string $slugPrototype): ?Slug
    {
        return $this->slugRepository->getSlugBySlugPrototypeAndScopeCriteria(
            $slugPrototype,
            $this->getScopeCriteria()
        );
    }

    private function getScopeCriteria(): ScopeCriteria
    {
        if (!$this->scopeCriteria) {
            $this->scopeCriteria = $this->scopeManager->getCriteria('web_content');
        }

        return $this->scopeCriteria;
    }
}
