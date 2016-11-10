<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugScopesData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class SlugRepositoryTest extends WebTestCase
{
    /**
     * @var SlugRepository
     */
    protected $repository;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Slug::class);
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');

        $this->loadFixtures(
            [
                LoadSlugScopesData::class
            ]
        );
    }

    public function testGetSlugByUrlAndScopeCriteriaAnonymous()
    {
        $criteria = $this->scopeManager->getCriteria('web_content');
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaUser()
    {
        $criteria = $this->scopeManager->getCriteria('web_content');
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_USER, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $this->assertSame($expected, $slug);
    }
}
