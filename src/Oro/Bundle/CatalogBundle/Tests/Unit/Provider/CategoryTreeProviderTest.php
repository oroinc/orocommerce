<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryTreeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CategoryRepository */
    protected $categoryRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var CategoryTreeProvider */
    protected $provider;

    public function setUp()
    {
        $this->categoryRepository = $this->getMockBuilder(
            'Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CategoryTreeProvider(
            $this->categoryRepository,
            $this->eventDispatcher
        );
    }

    public function testGetCategories()
    {
        $user = new AccountUser();

        $childCategory = new Category();
        $childCategory->setLevel(2);

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->addChildCategory($mainCategory);

        $categories = [$rootCategory, $mainCategory, $childCategory];
        $visibleCategories = [$rootCategory, $mainCategory, $childCategory];

        $this->categoryRepository->expects($this->once())
            ->method('getChildrenWithTitles')
            ->willReturn($categories);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CategoryTreeCreateAfterEvent::NAME, $event)
            ->willReturn($visibleCategories);

        $actual = $this->provider->getCategories($user, null, false);

        $this->assertEquals($visibleCategories, $actual);
    }
}
