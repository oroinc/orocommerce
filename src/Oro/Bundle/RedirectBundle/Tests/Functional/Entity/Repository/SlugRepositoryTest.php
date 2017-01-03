<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugScopesData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
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

        $organization = $this->getContainer()->get('doctrine')
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $token = new UsernamePasswordOrganizationToken(
            LoadAccountUserData::AUTH_USER,
            'admin',
            'key',
            $organization
        );
        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->loadFixtures(
            [
                LoadSlugScopesData::class
            ]
        );
    }

    public function testGetSlugByUrlAndScopeCriteriaAnonymous()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaUser()
    {
        /** @var AccountUser $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['account' => $account]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_USER, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaWhenSlugHasScopesThatNotMatches()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_PAGE, $criteria);
        $this->assertNull($slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopes()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesNotEmptyCriteria()
    {
        /** @var AccountUser $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['account' => $account]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithScopesMatched()
    {
        /** @var AccountUser $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['account' => $account]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_DUPLICATE_URL, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_TEST_DUPLICATE_URL);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithoutScopesMatched()
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_TEST_DUPLICATE_URL, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_TEST_DUPLICATE_REFERENCE);
        $this->assertSame($expected, $slug);
    }
}
