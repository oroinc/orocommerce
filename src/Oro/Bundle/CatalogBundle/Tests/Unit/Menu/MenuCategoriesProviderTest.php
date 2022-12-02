<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\UserBundle\Entity\UserInterface;

class MenuCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject $categoryTreeProvider;

    private MenuCategoriesProvider $provider;

    protected function setUp(): void
    {
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->provider = new MenuCategoriesProvider($this->categoryTreeProvider, $localizationHelper);

        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                static fn ($values, $localization = null) => (string)($values[0] ?? null) . $localization?->getId()
            );
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
        $localization = new LocalizationStub(42);
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
                    'title' => (string)$category1->getTitles()[0] . $localization->getId(),
                    'level' => $category1->getLevel(),
                ],
            ],
            $this->provider->getCategories($category1, $user, $localization)
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
                    'title' => (string)$category1->getTitles()[0],
                    'level' => $category1->getLevel(),
                ],
                $category12->getId() => [
                    'id' => $category12->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category12->getTitles()[0],
                    'level' => $category12->getLevel(),
                ],
                $category13->getId() => [
                    'id' => $category13->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category13->getTitles()[0],
                    'level' => $category13->getLevel(),
                ],
                $category131->getId() => [
                    'id' => $category131->getId(),
                    'parentId' => $category13->getId(),
                    'title' => (string)$category131->getTitles()[0],
                    'level' => $category131->getLevel(),
                ],
                $category14->getId() => [
                    'id' => $category14->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category14->getTitles()[0],
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
                    'title' => (string)$category1->getTitles()[0],
                    'level' => $category1->getLevel(),
                ],
                $category12->getId() => [
                    'id' => $category12->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category12->getTitles()[0],
                    'level' => $category12->getLevel(),
                ],
                $category13->getId() => [
                    'id' => $category13->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category13->getTitles()[0],
                    'level' => $category13->getLevel(),
                ],
                $category14->getId() => [
                    'id' => $category14->getId(),
                    'parentId' => $category1->getId(),
                    'title' => (string)$category14->getTitles()[0],
                    'level' => $category14->getLevel(),
                ],
            ],
            $this->provider->getCategories($category1, $user, null, ['tree_depth' => 1])
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
