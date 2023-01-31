<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tools\LocalizedFallbackValueHelper;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class MenuCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject $categoryTreeProvider;

    private MenuCategoriesProvider $provider;

    protected function setUp(): void
    {
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);

        $this->provider = new MenuCategoriesProvider($this->categoryTreeProvider);
    }

    public function testGetCategoriesWhenNoCategories(): void
    {
        $user = $this->createMock(UserInterface::class);
        $category = $this->createMock(Category::class);
        $this->categoryTreeProvider
            ->expects(self::once())
            ->method('getCategories')
            ->with($user, $category, true)
            ->willReturn([]);

        self::assertEquals([], $this->provider->getCategories($category, $user));
    }

    public function testGetCategoriesWhenSingleCategoryAndLocalization(): void
    {
        $user = $this->createMock(UserInterface::class);
        $category1 = $this->createCategory(1, null, 0);

        $this->categoryTreeProvider
            ->expects(self::any())
            ->method('getCategories')
            ->with($user, $category1, true)
            ->willReturn([$category1]);

        self::assertEquals(
            [
                $category1->getId() => [
                    'id' => $category1->getId(),
                    'parentId' => null,
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category1->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category1->getLevel(),
                ],
            ],
            $this->provider->getCategories($category1, $user)
        );
    }

    public function testGetCategoriesWhenManyCategories(): void
    {
        $user = $this->createMock(UserInterface::class);
        $category1 = $this->createCategory(1, null, 0);
        $category12 = $this->createCategory(12, $category1, 1);
        $category13 = $this->createCategory(13, $category1, 1);
        $category131 = $this->createCategory(131, $category13, 2);
        $category14 = $this->createCategory(14, $category1, 1);

        $this->categoryTreeProvider
            ->expects(self::any())
            ->method('getCategories')
            ->with($user, $category1, true)
            ->willReturn([$category1, $category12, $category13, $category131, $category14]);

        self::assertEquals(
            [
                $category1->getId() => [
                    'id' => $category1->getId(),
                    'parentId' => null,
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category1->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category1->getLevel(),
                ],
                $category12->getId() => [
                    'id' => $category12->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category12->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category12->getLevel(),
                ],
                $category13->getId() => [
                    'id' => $category13->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category13->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category13->getLevel(),
                ],
                $category131->getId() => [
                    'id' => $category131->getId(),
                    'parentId' => $category13->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category131->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category131->getLevel(),
                ],
                $category14->getId() => [
                    'id' => $category14->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category14->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category14->getLevel(),
                ],
            ],
            $this->provider->getCategories($category1, $user)
        );
    }

    public function testGetCategoriesWhenManyCategoriesAndDepth(): void
    {
        $user = $this->createMock(UserInterface::class);
        $category1 = $this->createCategory(1, null, 0);
        $category12 = $this->createCategory(12, $category1, 1);
        $category13 = $this->createCategory(13, $category1, 1);
        $category131 = $this->createCategory(131, $category13, 2);
        $category1311 = $this->createCategory(1311, $category131, 3);
        $category14 = $this->createCategory(14, $category1, 1);
        $category141 = $this->createCategory(141, $category14, 2);

        $this->categoryTreeProvider
            ->expects(self::any())
            ->method('getCategories')
            ->with($user, $category1, true)
            ->willReturn([
                $category1,
                $category12,
                $category13,
                $category131,
                $category1311,
                $category14,
                $category141,
            ]);

        self::assertEquals(
            [
                $category1->getId() => [
                    'id' => $category1->getId(),
                    'parentId' => null,
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category1->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category1->getLevel(),
                ],
                $category12->getId() => [
                    'id' => $category12->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category12->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category12->getLevel(),
                ],
                $category13->getId() => [
                    'id' => $category13->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category13->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category13->getLevel(),
                ],
                $category14->getId() => [
                    'id' => $category14->getId(),
                    'parentId' => $category1->getId(),
                    'titles' => LocalizedFallbackValueHelper::cloneCollection(
                        $category14->getTitles(),
                        LocalizedFallbackValue::class
                    ),
                    'level' => $category14->getLevel(),
                ],
            ],
            $this->provider->getCategories($category1, $user, ['tree_depth' => 1])
        );
    }

    private function createCategory(int $id, ?Category $parentCategory, int $level): CategoryStub
    {
        return (new CategoryStub())
            ->setId($id)
            ->addTitle((new CategoryTitle())->setString('Category ' . $id))
            ->setParentCategory($parentCategory)
            ->setLevel($level);
    }
}
