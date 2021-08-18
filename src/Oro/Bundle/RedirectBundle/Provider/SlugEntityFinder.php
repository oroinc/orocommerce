<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides methods to find a slug entity.
 */
class SlugEntityFinder
{
    private ManagerRegistry $doctrine;
    private ScopeManager $scopeManager;
    private AclHelper $aclHelper;
    private ?ScopeCriteria $scopeCriteria = null;

    public function __construct(ManagerRegistry $doctrine, ScopeManager $scopeManager, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->scopeManager = $scopeManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * Finds a slug entity by the given URL.
     */
    public function findSlugEntityByUrl(string $url): ?Slug
    {
        return $this->getSlugRepository()->getSlugByUrlAndScopeCriteria(
            $url,
            $this->getScopeCriteria(),
            $this->aclHelper
        );
    }

    /**
     * Finds a slug entity by the given slug prototype.
     */
    public function findSlugEntityBySlugPrototype(string $slugPrototype): ?Slug
    {
        return $this->getSlugRepository()->getSlugBySlugPrototypeAndScopeCriteria(
            $slugPrototype,
            $this->getScopeCriteria(),
            $this->aclHelper
        );
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getManagerForClass(Slug::class)
            ->getRepository(Slug::class);
    }

    private function getScopeCriteria(): ScopeCriteria
    {
        if (!$this->scopeCriteria) {
            $this->scopeCriteria = $this->scopeManager->getCriteria('web_content');
        }

        return $this->scopeCriteria;
    }
}
