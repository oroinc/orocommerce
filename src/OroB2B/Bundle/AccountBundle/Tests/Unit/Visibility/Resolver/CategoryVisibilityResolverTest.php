<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Resolver;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolver;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CategoryVisibilityResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new CategoryVisibilityResolver($this->registry);
    }

    public function testIsCategoryVisible()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->isCategoryVisible($category);
    }

    public function testGetVisibleCategoryIds()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getVisibleCategoryIds();
    }

    public function testGetHiddenCategoryIds()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getHiddenCategoryIds();
    }

    public function testIsCategoryVisibleForAccountGroup()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 123]);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, $accountGroup);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
    }

    public function testGetVisibleCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $accountGroup);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getVisibleCategoryIdsForAccountGroup($accountGroup);
    }

    public function testGetHiddenCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 42]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN, $accountGroup);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getHiddenCategoryIdsForAccountGroup($accountGroup);
    }

    public function testIsCategoryVisibleForAccount()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 10]);

        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('isCategoryVisible')
            ->with($category, $account);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->isCategoryVisibleForAccount($category, $account);
    }

    public function testGetVisibleCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $account);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getVisibleCategoryIdsForAccount($account);
    }

    public function testGetHiddenCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 20]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository = $this
            ->getMockBuilder(
                'OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $categoryVisibilityResolvedRepository->expects($this->once())
            ->method('getCategoryIdsByVisibility')
            ->with(BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN, $account);

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->willReturn($categoryVisibilityResolvedRepository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->resolver->getHiddenCategoryIdsForAccount($account);
    }
}
