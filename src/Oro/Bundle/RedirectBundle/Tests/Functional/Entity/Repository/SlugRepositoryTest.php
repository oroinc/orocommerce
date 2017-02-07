<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugScopesData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
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
            LoadCustomerUserData::AUTH_USER,
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
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
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
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
        $slug = $this->repository->getSlugByUrlAndScopeCriteria(LoadSlugsData::SLUG_URL_ANONYMOUS, $criteria);
        $expected = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);
        $this->assertSame($expected, $slug);
    }

    public function testGetSlugByUrlAndScopeCriteriaSlugWithScopesMatched()
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['customer' => $customer]);
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

    public function testFindOneBySlugWithoutScopes()
    {
        $actual = $this->repository->findOneBySlugWithoutScopes('/slug/first');

        $this->assertNotEmpty($actual);
        $this->assertEquals($this->getReference('reference:/slug/first')->getId(), $actual->getId());
    }

    public function testFindOneBySlugWithoutScopesScopedSlug()
    {
        $actual = $this->repository->findOneBySlugWithoutScopes('/slug/page');

        $this->assertEmpty($actual);
    }

    public function testFindOneBySlugWithoutScopesWithRestriction()
    {
        $restrictedEntity = $this->createMock(SluggableInterface::class);
        $restrictedEntity->expects($this->once())
            ->method('getSlugs')
            ->willReturn([$this->getReference('reference:/slug/first')]);

        $actual = $this->repository->findOneBySlugWithoutScopes('/slug/first', $restrictedEntity);
        $this->assertEmpty($actual);
    }

    /**
     * @dataProvider findAllByPatternWithoutScopeDataProvider
     * @param string $pattern
     * @param array $expected
     */
    public function testFindAllByPatternWithoutScopes($pattern, array $expected)
    {
        $actual = $this->repository->findAllByPatternWithoutScopes($pattern);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function findAllByPatternWithoutScopeDataProvider()
    {
        return [
            [
                'pattern' => '/slug/f%',
                'expected' => [
                    '/slug/first'
                ]
            ],
            [
                'pattern' => '/slug%',
                'expected' => [
                    '/slug/anonymous',
                    '/slug/first',
                ]
            ]
        ];
    }

    public function testFindAllByPatternWithoutScopesWithRestriction()
    {
        $restrictedEntity = $this->createMock(SluggableInterface::class);
        $restrictedEntity->expects($this->once())
            ->method('getSlugs')
            ->willReturn([$this->getReference('reference:/slug/first')]);

        $actual = $this->repository->findAllByPatternWithoutScopes('/slug/f%', $restrictedEntity);
        $this->assertEmpty($actual);
    }

    public function testFindAllByPatternWithoutScopesScopedSlug()
    {
        $actual = $this->repository->findAllByPatternWithoutScopes('/slug/page');

        $this->assertEmpty($actual);
    }
}
