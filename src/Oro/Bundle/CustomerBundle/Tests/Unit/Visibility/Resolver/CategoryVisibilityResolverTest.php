<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $visibleCategoryIds = [1, 2, 3];

    /**
     * @var array
     */
    protected $hiddenCategoryIds = [1, 2, 3];

    /**
     * @var CategoryVisibilityResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_customer.category_visibility')
            ->willReturn(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);

        $this->resolver = new CategoryVisibilityResolver($this->registry, $this->configManager);
    }

    public function testIsCategoryVisible()
    {
        /** @var Category $category */
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE)
            ->willReturn(true);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertTrue($this->resolver->isCategoryVisible($category));
    }

    public function testGetVisibleCategoryIds()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->visibleCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($this->visibleCategoryIds, $this->resolver->getVisibleCategoryIds());
    }

    public function testGetHiddenCategoryIds()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->hiddenCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(CategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($this->hiddenCategoryIds, $this->resolver->getHiddenCategoryIds());
    }

    public function testIsCategoryVisibleForAccountGroup()
    {
        /** @var Category $category */
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', ['id' => 123]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, $accountGroup, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE)
            ->willReturn(false);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertFalse($this->resolver->isCategoryVisibleForAccountGroup($category, $accountGroup));
    }

    public function testGetVisibleCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $accountGroup,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->visibleCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals(
            $this->visibleCategoryIds,
            $this->resolver->getVisibleCategoryIdsForAccountGroup($accountGroup)
        );
    }

    public function testGetHiddenCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $accountGroup,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->hiddenCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountGroupCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals(
            $this->hiddenCategoryIds,
            $this->resolver->getHiddenCategoryIdsForAccountGroup($accountGroup)
        );
    }

    public function testIsCategoryVisibleForAccount()
    {
        /** @var Category $category */
        $category = $this->getEntity('Oro\Bundle\CatalogBundle\Entity\Category', ['id' => 10]);

        /** @var Account $account */
        $account = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, $account, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE)
            ->willReturn(true);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertTrue($this->resolver->isCategoryVisibleForAccount($category, $account));
    }

    public function testGetVisibleCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                $account,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->visibleCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals(
            $this->visibleCategoryIds,
            $this->resolver->getVisibleCategoryIdsForAccount($account)
        );
    }

    public function testGetHiddenCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(
                BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                $account,
                BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            )
            ->willReturn($this->hiddenCategoryIds);

        $em->expects($this->once())
            ->method('getRepository')
            ->with(AccountCategoryVisibilityResolved::class)
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals(
            $this->hiddenCategoryIds,
            $this->resolver->getHiddenCategoryIdsForAccount($account)
        );
    }
}
