<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CategoryRepository */
    protected $categoryRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var CategoryTreeProvider */
    protected $provider;

    /** @var MasterCatalogRootProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $masterCatalogRootProvider;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->getMockBuilder(
            'Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->masterCatalogRootProvider = $this->createMock(MasterCatalogRootProvider::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->provider = new CategoryTreeProvider(
            $registry,
            $this->eventDispatcher,
            $this->masterCatalogRootProvider
        );
    }

    public function testGetCategories()
    {
        $user = new CustomerUser();

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
            ->method('getChildren')
            ->willReturn($categories);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, CategoryTreeCreateAfterEvent::NAME)
            ->willReturn($visibleCategories);

        $this->masterCatalogRootProvider
            ->expects($this->never())
            ->method('getMasterCatalogRoot');

        $actual = $this->provider->getCategories($user, $rootCategory, false);

        $this->assertEquals($visibleCategories, $actual);
    }

    public function testGetCategoriesWithNoRootPassed()
    {
        $user = new CustomerUser();

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
            ->method('getChildren')
            ->willReturn($categories);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, CategoryTreeCreateAfterEvent::NAME)
            ->willReturn($visibleCategories);

        $this->masterCatalogRootProvider
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $actual = $this->provider->getCategories($user, null, false);

        $this->assertEquals($visibleCategories, $actual);
    }
}
