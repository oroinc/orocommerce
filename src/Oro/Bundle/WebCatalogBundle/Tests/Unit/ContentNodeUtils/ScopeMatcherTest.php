<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentNodeUtils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ScopeMatcher;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeMatcherTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    /**
     * @var ScopeMatcher
     */
    private $matcher;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = new ScopeMatcher(
            $this->registry,
            $this->scopeManager
        );
    }

    public function testGetUsedScopes()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 2]);
        $scopes = [$scope];

        $repository = $this->getWebCatalogRepositoryMock();
        $repository->expects($this->once())
            ->method('getUsedScopes')
            ->with($webCatalog)
            ->willReturn($scopes);

        $this->assertEquals($scopes, $this->matcher->getUsedScopes($webCatalog));
    }

    public function testGetMatchingScopes()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 2]);
        $scopes = [$scope];

        /** @var ScopeCriteria|\PHPUnit_Framework_MockObject_MockObject $scopeCriteria */
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())
            ->method('toArray')
            ->willReturn(['webCatalog' => $webCatalog]);

        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope)
            ->willReturn($scopeCriteria);

        $repository = $this->getWebCatalogRepositoryMock();
        $repository->expects($this->once())
            ->method('getMatchingScopes')
            ->with($webCatalog, $scopeCriteria)
            ->willReturn($scopes);

        $this->assertEquals($scopes, $this->matcher->getMatchingScopes($scope));
    }

    public function testGetMatchingScopesForCriteriaWithoutWebCatalog()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 2]);

        /** @var ScopeCriteria|\PHPUnit_Framework_MockObject_MockObject $scopeCriteria */
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope)
            ->willReturn($scopeCriteria);

        $repository = $this->getWebCatalogRepositoryMock();
        $repository->expects($this->never())
            ->method('getMatchingScopes');

        $this->assertEquals([], $this->matcher->getMatchingScopes($scope));
    }

    public function testGetMatchingScopePriority()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        $scopes = [$scope1, $scope2];

        /** @var ScopeCriteria|\PHPUnit_Framework_MockObject_MockObject $scopeCriteria */
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())
            ->method('toArray')
            ->willReturn(['webCatalog' => $webCatalog]);

        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope1)
            ->willReturn($scopeCriteria);

        $repository = $this->getWebCatalogRepositoryMock();
        $repository->expects($this->once())
            ->method('getMatchingScopes')
            ->with($webCatalog, $scopeCriteria)
            ->willReturn($scopes);

        $this->assertEquals(1, $this->matcher->getMatchingScopePriority(new ArrayCollection([$scope2]), $scope1));
    }

    public function testGetBestMatchByScope()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => 1]);
        /** @var Scope $scope1 */
        $scope1 = $this->getEntity(Scope::class, ['id' => 1]);
        /** @var Scope $scope2 */
        $scope2 = $this->getEntity(Scope::class, ['id' => 2]);
        /** @var Scope $scope3 */
        $scope3 = $this->getEntity(Scope::class, ['id' => 3]);
        $scopes = [$scope1, $scope2];

        /** @var ScopeCriteria|\PHPUnit_Framework_MockObject_MockObject $scopeCriteria */
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeCriteria->expects($this->once())
            ->method('toArray')
            ->willReturn(['webCatalog' => $webCatalog]);

        $this->scopeManager->expects($this->once())
            ->method('getCriteriaByScope')
            ->with($scope2)
            ->willReturn($scopeCriteria);

        $repository = $this->getWebCatalogRepositoryMock();
        $repository->expects($this->once())
            ->method('getMatchingScopes')
            ->with($webCatalog, $scopeCriteria)
            ->willReturn($scopes);

        $entity1 = $this->createMock(ScopeCollectionAwareInterface::class);
        $entity1->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope1]));
        $entity2 = $this->createMock(ScopeCollectionAwareInterface::class);
        $entity2->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope1, $scope2]));
        $entity3 = $this->createMock(ScopeCollectionAwareInterface::class);
        $entity3->expects($this->any())
            ->method('getScopes')
            ->willReturn(new ArrayCollection([$scope3]));
        $entitiesCollection = new ArrayCollection([$entity1, $entity2, $entity3]);

        $this->assertSame($entity2, $this->matcher->getBestMatchByScope($entitiesCollection, $scope2));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WebCatalogRepository
     */
    protected function getWebCatalogRepositoryMock()
    {
        $repository = $this->getMockBuilder(WebCatalogRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $repository;
    }
}
