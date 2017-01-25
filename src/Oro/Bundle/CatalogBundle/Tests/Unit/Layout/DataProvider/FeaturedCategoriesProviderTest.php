<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FeaturedCategoriesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryTreeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeProvider;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var FeaturedCategoriesProvider
     */
    protected $featuredCategoriesProvider;

    protected function setUp()
    {
        $this->categoryTreeProvider = $this->getMockBuilder(CategoryTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featuredCategoriesProvider = new FeaturedCategoriesProvider(
            $this->categoryTreeProvider,
            $this->tokenStorage
        );
    }

    public function testGetAll()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new CustomerUser()));

        $firstCategory = $this->createMock(Category::class);
        $firstCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $firstCategory->expects($this->any())
            ->method('getLevel')
            ->will($this->returnValue(2));

        $secondCategory = $this->createMock(Category::class);
        $secondCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $secondCategory->expects($this->any())
            ->method('getLevel')
            ->will($this->returnValue(3));


        $categoryIds = [5, 6, 0, 1];
        $categories = [1 => $firstCategory, 2 => $secondCategory];
        $visibleCategories = [1 => $firstCategory];

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($this->tokenStorage->getToken()->getUser())
            ->willReturn($categories);

        $actual = $this->featuredCategoriesProvider->getAll($categoryIds);
        $this->assertEquals($visibleCategories, $actual);
    }
}
