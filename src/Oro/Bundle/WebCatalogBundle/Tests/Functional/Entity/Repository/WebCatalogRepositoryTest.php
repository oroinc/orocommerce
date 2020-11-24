<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogScopes;

class WebCatalogRepositoryTest extends WebTestCase
{
    /**
     * @var WebCatalogRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadWebCatalogScopes::class
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(WebCatalog::class)
            ->getRepository(WebCatalog::class);
    }

    public function testGetUsedScopes()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var Scope $scope1 */
        $scope1 = $this->getReference(LoadWebCatalogScopes::SCOPE1);

        $usedScopes = $this->repository->getUsedScopes($webCatalog);
        $this->assertCount(1, $usedScopes);
        $this->assertContains($scope1, $usedScopes);
    }

    public function testGetMatchingScopes()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        /** @var Scope $scope1 */
        $scope1 = $this->getReference(LoadWebCatalogScopes::SCOPE1);
        /** @var Scope $scope2 */
        $scope2 = $this->getReference(LoadWebCatalogScopes::SCOPE2);

        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $scopeCriteria = $scopeManager->getCriteria(
            'web_content',
            ['webCatalog' => $webCatalog, 'customer' => $customer]
        );

        $scopes = $this->repository->getMatchingScopes($webCatalog, $scopeCriteria);
        $this->assertCount(2, $scopes);
        $this->assertEquals($scope2->getId(), $scopes[0]->getId());
        $this->assertEquals($scope1->getId(), $scopes[1]->getId());
    }

    public function testGetUsedScopesIds()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var Scope $scope1 */
        $scope1 = $this->getReference(LoadWebCatalogScopes::SCOPE1);
        /** @var Scope $scope2 */
        $scope2 = $this->getReference(LoadWebCatalogScopes::SCOPE2);

        $usedScopesIds = $this->repository->getUsedScopesIds($webCatalog);
        self::assertEqualsCanonicalizing([$scope2->getId(), $scope1->getId()], $usedScopesIds);
    }
}
