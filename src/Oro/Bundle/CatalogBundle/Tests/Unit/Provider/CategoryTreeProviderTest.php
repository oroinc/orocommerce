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
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryTreeProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryRepository|\PHPUnit\Framework\MockObject\MockObject $categoryRepository;
    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;
    private MasterCatalogRootProvider|\PHPUnit\Framework\MockObject\MockObject $masterCatalogRootProvider;
    private CategoryTreeProvider $provider;

    #[\Override]
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

    private function getCategory(int $id): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);

        return $category;
    }

    public function testGetCategories(): void
    {
        $user = new CustomerUser();

        $childCategory = $this->getCategory(1);
        $childCategory->setLevel(2);

        $mainCategory = $this->getCategory(2);
        $mainCategory->setLevel(1);
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = $this->getCategory(3);
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

        $this->masterCatalogRootProvider->expects(self::never())
            ->method('getMasterCatalogRoot');

        $actual = $this->provider->getCategories($user, $rootCategory, false);

        self::assertEquals($visibleCategories, $actual);
    }

    public function testGetCategoriesWithNoRootPassed(): void
    {
        $user = new CustomerUser();

        $childCategory = $this->getCategory(1);
        $childCategory->setLevel(2);

        $mainCategory = $this->getCategory(2);
        $mainCategory->setLevel(1);
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = $this->getCategory(3);
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

        $this->masterCatalogRootProvider->expects(self::once())
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
        $categoryA = $this->getCategory(1);
        $categoryB = $this->getCategory(2);

        $originalCategories = [$categoryA, $categoryB];
        $this->categoryRepository->expects(self::once())
            ->method('getPath')
            ->with($categoryA)
            ->willReturn($originalCategories);

        $expectedEvent = new CategoryTreeCreateAfterEvent($originalCategories);
        $expectedEvent->setUser($user);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($expectedEvent, CategoryTreeCreateAfterEvent::NAME)
            ->willReturnCallback(function (CategoryTreeCreateAfterEvent $event) {
                $categories = $event->getCategories();
                array_shift($categories);
                $event->setCategories($categories);

                return $event;
            });

        self::assertSame(
            [$categoryB],
            $this->provider->getParentCategories($user, $categoryA)
        );
    }

    public function getUserDataProvider(): array
    {
        return [
            'null' => [
                'user' => null
            ],
            'not customer user' => [
                'user' => $this->createMock(UserInterface::class)
            ],
            'customer user' => [
                'user' => new CustomerUser()
            ]
        ];
    }
}
