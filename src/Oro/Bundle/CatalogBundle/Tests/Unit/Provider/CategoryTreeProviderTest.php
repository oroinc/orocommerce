<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $categoryRepository;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private MasterCatalogRootProvider|\PHPUnit\Framework\MockObject\MockObject $masterCatalogRootProvider;

    private CategoryTreeProvider $provider;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->masterCatalogRootProvider = $this->createMock(MasterCatalogRootProvider::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->provider = new CategoryTreeProvider(
            $registry,
            $this->eventDispatcher,
            $this->masterCatalogRootProvider
        );
    }

    public function testGetCategories(): void
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

        $this->categoryRepository->expects(self::once())
            ->method('getChildren')
            ->willReturn($categories);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, CategoryTreeCreateAfterEvent::NAME)
            ->willReturn($event);

        $this->masterCatalogRootProvider
            ->expects(self::never())
            ->method('getMasterCatalogRoot');

        $actual = $this->provider->getCategories($user, $rootCategory, false);

        self::assertEquals($visibleCategories, $actual);
    }

    public function testGetCategoriesWithNoRootPassed(): void
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

        $this->categoryRepository->expects(self::once())
            ->method('getChildren')
            ->willReturn($categories);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, CategoryTreeCreateAfterEvent::NAME)
            ->willReturn($event);

        $this->masterCatalogRootProvider
            ->expects(self::once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $actual = $this->provider->getCategories($user, null, false);

        self::assertEquals($visibleCategories, $actual);
    }

    /**
     * @dataProvider getUserDataProvider
     */
    public function testGetParentCategories(?UserInterface $user): void
    {
        $categoryA = new CategoryStub();
        $categoryA->setId(1);

        $categoryB = new CategoryStub();
        $categoryB->setId(2);

        $originalCategories = [$categoryA, $categoryB];
        $this->categoryRepository->expects(self::once())
            ->method('getPath')
            ->with($categoryA)
            ->willReturn($originalCategories);

        $this->mockEventDispatcher($user, $originalCategories);

        self::assertSame(
            [$categoryB],
            $this->provider->getParentCategories($user, $categoryA)
        );
    }


    public function getUserDataProvider(): array
    {
        return [
            'null' => [
                'user' => null,
            ],
            'not customer user' => [
                'user' => $this->createMock(UserInterface::class),
            ],
            'customer user' => [
                'user' => new CustomerUserStub(1),
            ],
        ];
    }

    private function mockEventDispatcher(?UserInterface $expectedUser, array $originalCategories): void
    {
        $expectedEvent = new CategoryTreeCreateAfterEvent($originalCategories);
        $expectedEvent->setUser($expectedUser);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($expectedEvent, CategoryTreeCreateAfterEvent::NAME)
            ->willReturnCallback(
                function (CategoryTreeCreateAfterEvent $event) {
                    $categories = $event->getCategories();
                    array_shift($categories);
                    $event->setCategories($categories);

                    return $event;
                }
            );
    }
}
