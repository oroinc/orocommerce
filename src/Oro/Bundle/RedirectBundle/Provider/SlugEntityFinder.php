<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Helper\SlugQueryRestrictionHelperInterface;
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
    private SlugQueryRestrictionHelperInterface $slugQueryRestrictionHelper;

    public function __construct(ManagerRegistry $doctrine, ScopeManager $scopeManager, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->scopeManager = $scopeManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @deprecated This method will be removed in 5.1
     *
     * @param SlugQueryRestrictionHelperInterface $slugQueryRestrictionHelper
     * @return void
     */
    public function setSlugQueryRestrictionHelper(SlugQueryRestrictionHelperInterface $slugQueryRestrictionHelper): void
    {
        $this->slugQueryRestrictionHelper = $slugQueryRestrictionHelper;
    }

    /**
     * Finds a slug entity by the given URL.
     */
    public function findSlugEntityByUrl(string $url): ?Slug
    {
        return $this->getSlugRepository()->getSlugByUrlAndScopeCriteriaWithSlugLocalization(
            $url,
            $this->getScopeCriteria(),
            $this->slugQueryRestrictionHelper
        );
    }

    /**
     * Finds a slug entity by the given slug prototype.
     */
    public function findSlugEntityBySlugPrototype(string $slugPrototype): ?Slug
    {
        return $this->getSlugRepository()->getSlugBySlugPrototypeAndScopeCriteriaWithSlugLocalization(
            $slugPrototype,
            $this->getScopeCriteria(),
            $this->slugQueryRestrictionHelper
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
