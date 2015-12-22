<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Resolver;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolver;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CategoryVisibilityStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var CategoryVisibilityResolver
     */
    protected $resolver;

    public function setUp()
    {
        $this->storage = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new CategoryVisibilityResolver($this->storage);
    }

    public function testIsCategoryVisible()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 10]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityData')
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertTrue($this->resolver->isCategoryVisible($category));
    }

    public function testGetVisibleCategoryIds()
    {
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityData')
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([10], $this->resolver->getVisibleCategoryIds());
    }

    public function testGetHiddenCategoryIds()
    {
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityData')
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([], $this->resolver->getHiddenCategoryIds());
    }

    public function testIsCategoryVisibleForAccountGroup()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 10]);
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccountGroup')
            ->with($accountGroup)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertTrue($this->resolver->isCategoryVisibleForAccountGroup($category, $accountGroup));
    }

    public function testGetVisibleCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccountGroup')
            ->with($accountGroup)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([10], $this->resolver->getVisibleCategoryIdsForAccountGroup($accountGroup));
    }

    public function testGetHiddenCategoryIdsForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccountGroup')
            ->with($accountGroup)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([], $this->resolver->getHiddenCategoryIdsForAccountGroup($accountGroup));
    }

    public function testIsCategoryVisibleForAccount()
    {
        /** @var Category $category */
        $category = $this->getEntity('OroB2B\Bundle\CatalogBundle\Entity\Category', ['id' => 10]);
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccount')
            ->with($account)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertTrue($this->resolver->isCategoryVisibleForAccount($category, $account));
    }

    public function testGetVisibleCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccount')
            ->with($account)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([10], $this->resolver->getVisibleCategoryIdsForAccount($account));
    }

    public function testGetHiddenCategoryIdsForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 5]);
        $this->storage->expects($this->once())
            ->method('getCategoryVisibilityDataForAccount')
            ->with($account)
            ->willReturn(new CategoryVisibilityData([10], []));
        $this->assertEquals([], $this->resolver->getHiddenCategoryIdsForAccount($account));
    }
}
