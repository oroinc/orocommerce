<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Visibility\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryVisibilityResolverTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var array */
    private $visibleCategoryIds = [1, 2, 3];

    /** @var array */
    private $hiddenCategoryIds = [1, 2, 3];

    /** @var CategoryVisibilityResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_visibility.category_visibility')
            ->willReturn(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);

        $this->resolver = new CategoryVisibilityResolver($this->doctrine, $this->configManager, $this->scopeManager);
    }

    /**
     * @param Scope[] $scopes
     */
    private function expectEntityManagerGetReference(array $scopes)
    {
        $this->em->expects($this->exactly(count($scopes)))
            ->method('getReference')
            ->with(Scope::class)
            ->willReturnCallback(function ($entityName, $id) use ($scopes) {
                foreach ($scopes as $scope) {
                    if ($scope->getId() === $id) {
                        return $scope;
                    }
                }
                throw new \LogicException(sprintf('Unknown scope ID: %s.', $id));
            });
    }

    public function testIsCategoryVisible()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 42]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE)
            ->willReturn(true);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertTrue($this->resolver->isCategoryVisible($category));
    }

    public function testGetVisibleCategoryIds()
    {
        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->visibleCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals($this->visibleCategoryIds, $this->resolver->getVisibleCategoryIds());
    }

    public function testGetHiddenCategoryIds()
    {
        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->hiddenCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals($this->hiddenCategoryIds, $this->resolver->getHiddenCategoryIds());
    }

    public function testIsCategoryVisibleForCustomerGroup()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 123]);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 42]);

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, ['customerGroup' => $customerGroup])
            ->willReturn($scope->getId());
        $this->expectEntityManagerGetReference([$scope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerGroupCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $scope)
            ->willReturn(false);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertFalse($this->resolver->isCategoryVisibleForCustomerGroup($category, $customerGroup));
    }

    public function testGetVisibleCategoryIdsForCustomerGroup()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 42]);

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, ['customerGroup' => $customerGroup])
            ->willReturn($scope->getId());
        $this->expectEntityManagerGetReference([$scope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerGroupCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->visibleCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals(
            $this->visibleCategoryIds,
            $this->resolver->getVisibleCategoryIdsForCustomerGroup($customerGroup)
        );
    }

    public function testGetHiddenCategoryIdsForCustomerGroup()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 42]);

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $this->scopeManager->expects($this->once())
            ->method('findId')
            ->with(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, ['customerGroup' => $customerGroup])
            ->willReturn($scope->getId());
        $this->expectEntityManagerGetReference([$scope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerGroupCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $scope,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->hiddenCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals(
            $this->hiddenCategoryIds,
            $this->resolver->getHiddenCategoryIdsForCustomerGroup($customerGroup)
        );
    }

    public function testIsCategoryVisibleForCustomer()
    {
        /** @var Category $category */
        $category = $this->getEntity(Category::class, ['id' => 10]);

        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 20]);
        $customer->setGroup($this->getEntity(CustomerGroup::class, ['id' => 1]));

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $groupScope = $this->getEntity(Scope::class, ['id' => 2]);
        $this->scopeManager->expects($this->exactly(2))
            ->method('findId')
            ->willReturnMap([
                [
                    CustomerCategoryVisibility::VISIBILITY_TYPE,
                    ['customer' => $customer],
                    $scope->getId()
                ],
                [
                    CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
                    ['customerGroup' => $customer->getGroup()],
                    $groupScope->getId()
                ]
            ]);
        $this->expectEntityManagerGetReference([$scope, $groupScope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $scope, $groupScope)
            ->willReturn(true);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertTrue($this->resolver->isCategoryVisibleForCustomer($category, $customer));
    }

    public function testGetVisibleCategoryIdsForCustomer()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 20]);
        $customer->setGroup($this->getEntity(CustomerGroup::class, ['id' => 1]));

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $groupScope = $this->getEntity(Scope::class, ['id' => 2]);
        $this->scopeManager->expects($this->exactly(2))
            ->method('findId')
            ->willReturnMap([
                [
                    CustomerCategoryVisibility::VISIBILITY_TYPE,
                    ['customer' => $customer],
                    $scope->getId()
                ],
                [
                    CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
                    ['customerGroup' => $customer->getGroup()],
                    $groupScope->getId()
                ]
            ]);
        $this->expectEntityManagerGetReference([$scope, $groupScope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $groupScope
            )
            ->willReturn($this->visibleCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals(
            $this->visibleCategoryIds,
            $this->resolver->getVisibleCategoryIdsForCustomer($customer)
        );
    }

    public function testGetHiddenCategoryIdsForCustomer()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 20]);
        $customer->setGroup($this->getEntity(CustomerGroup::class, ['id' => 1]));

        $scope = $this->getEntity(Scope::class, ['id' => 1]);
        $groupScope = $this->getEntity(Scope::class, ['id' => 2]);
        $this->scopeManager->expects($this->exactly(2))
            ->method('findId')
            ->willReturnMap([
                [
                    CustomerCategoryVisibility::VISIBILITY_TYPE,
                    ['customer' => $customer],
                    $scope->getId()
                ],
                [
                    CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
                    ['customerGroup' => $customer->getGroup()],
                    $groupScope->getId()
                ]
            ]);
        $this->expectEntityManagerGetReference([$scope, $groupScope]);

        $categoryVisibilityResolvedRepository = $this->createMock(Repository\CustomerCategoryRepository::class);

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $scope,
                $groupScope
            )
            ->willReturn($this->hiddenCategoryIds);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(VisibilityResolved\CustomerCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->assertEquals(
            $this->hiddenCategoryIds,
            $this->resolver->getHiddenCategoryIdsForCustomer($customer)
        );
    }
}
