<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Provides methods to find a slug entity.
 */
class SlugEntityFinder
{
    private ManagerRegistry $doctrine;
    private ScopeManager $scopeManager;
    private ?ScopeCriteria $scopeCriteria = null;

    public function __construct(ManagerRegistry $doctrine, ScopeManager $scopeManager)
    {
        $this->doctrine = $doctrine;
        $this->scopeManager = $scopeManager;
    }

    /**
     * Finds a slug entity by the given URL.
     */
    public function findSlugEntityByUrl(string $url): ?Slug
    {
        return $this->getSlugRepository()->getSlugByUrlAndScopeCriteria($url, $this->getScopeCriteria());
    }

    /**
     * Finds a slug entity by the given slug prototype.
     */
    public function findSlugEntityBySlugPrototype(string $slugPrototype): ?Slug
    {
        return $this->getSlugRepository()->getSlugBySlugPrototypeAndScopeCriteria(
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

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getRepository(Slug::class);
    }
}
