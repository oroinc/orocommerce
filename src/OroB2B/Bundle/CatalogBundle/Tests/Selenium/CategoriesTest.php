<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

use OroB2B\Bundle\CatalogBundle\Tests\Selenium\Pages\Categories;

class CategoriesTest extends Selenium2TestCase
{
    const MASTER_CATALOG = 'Master catalog';

    /**
     * @var string
     */
    protected static $firstCategory;

    /**
     * @var string
     */
    protected static $secondCategory;

    public static function setUpBeforeClass()
    {
        $suffix = uniqid();
        self::$firstCategory = 'First category ' . $suffix;
        self::$secondCategory = 'Second category ' . $suffix;
    }

    public function testCreateCategories()
    {
        /** @var Categories $categories */
        $categories = $this->login()->openCategories('OroB2B\Bundle\CatalogBundle');

        // preconditions
        $categories->assertTitle('Categories - Catalog Management')
            ->assertCategoryExists(self::MASTER_CATALOG);

        // create first level category
        $categories->createCategory()
            ->assertTitle('Create Category - Categories - Catalog Management')
            ->setDefaultTitle(self::$firstCategory)
            ->save();

        // assert first level category created
        $categories->assertTitle('Categories - Catalog Management')
            ->assertMessage('Category has been saved')
            ->assertCategoryExists(self::$firstCategory)
            ->assertContainsSubcategory(self::MASTER_CATALOG, self::$firstCategory);

        // create second level category
        $categories->openCategory(self::$firstCategory)
            ->assertTitle(self::$firstCategory . ' - Edit - Categories - Catalog Management')
            ->createSubcategory()
            ->assertTitle('Create Category - Categories - Catalog Management')
            ->setDefaultTitle(self::$secondCategory)
            ->save();

        // assert second level category created
        $categories->assertTitle('Categories - Catalog Management')
            ->assertMessage('Category has been saved')
            ->openTreeSubcategories(self::$firstCategory)
            ->assertCategoryExists(self::$secondCategory)
            ->assertContainsSubcategory(self::$firstCategory, self::$secondCategory)
            ->openCategory(self::$secondCategory)
            ->assertTitle(self::$secondCategory . ' - Edit - Categories - Catalog Management');
    }

    /**
     * @depends testCreateCategories
     */
    public function testDragAndDrop()
    {
        /** @var Categories $categories */
        $categories = $this->login()->openCategories('OroB2B\Bundle\CatalogBundle');

        /**
         * preconditions
         *
         *  - Master catalog
         *      - First category
         *          - Second category
         */
        $categories->assertCategoryExists(self::MASTER_CATALOG)
            ->assertCategoryExists(self::$firstCategory)
            ->assertContainsSubcategory(self::MASTER_CATALOG, self::$firstCategory)
            ->openTreeSubcategories(self::$firstCategory)
            ->assertCategoryExists(self::$secondCategory)
            ->assertContainsSubcategory(self::$firstCategory, self::$secondCategory);

        /**
         * move second category to master catalog
         *
         *  - Master catalog
         *      - Second category
         *      - First category
         */
        $categories->dragAndDrop(self::$secondCategory, self::MASTER_CATALOG)
            ->assertContainsSubcategory(self::MASTER_CATALOG, self::$secondCategory)
            ->assertNotContainSubcategory(self::$firstCategory, self::$secondCategory)
            ->assertCategoryAfter(self::$secondCategory, self::$firstCategory);

        /**
         * move second category after first category
         *
         *  - Master catalog
         *      - First category
         *      - Second category
         */
        $categories->dragAndDropAfterTarget(self::$secondCategory, self::$firstCategory)
            ->assertCategoryAfter(self::$firstCategory, self::$secondCategory);

        /**
         * move first category to second category
         *
         *  - Master catalog
         *      - Second category
         *          - First category
         */
        $categories->dragAndDrop(self::$firstCategory, self::$secondCategory)
            ->openTreeSubcategories(self::$secondCategory)
            ->assertContainsSubcategory(self::$secondCategory, self::$firstCategory)
            ->assertNotContainSubcategory(self::MASTER_CATALOG, self::$firstCategory);
    }

    /**
     * @depends testDragAndDrop
     */
    public function testDeleteCategories()
    {
        /** @var Categories $categories */
        $categories = $this->login()->openCategories('OroB2B\Bundle\CatalogBundle');

        /**
         * preconditions
         *
         *  - Master catalog
         *      - Second category
         *          - First category
         */
        $categories->assertCategoryExists(self::MASTER_CATALOG)
            ->assertCategoryExists(self::$secondCategory)
            ->assertContainsSubcategory(self::MASTER_CATALOG, self::$secondCategory)
            ->openTreeSubcategories(self::$secondCategory)
            ->assertCategoryExists(self::$firstCategory)
            ->assertContainsSubcategory(self::$secondCategory, self::$firstCategory);

        // master catalog can't be removed
        $categories->openCategory(self::MASTER_CATALOG)
            ->assertDeleteNotAllowed();

        // delete second category, first should be removed automatically
        $categories->openCategory(self::$secondCategory)
            ->assertDeleteAllowed()
            ->deleteCategory();

        // assert categories removed
        $categories->assertTitle('Categories - Catalog Management')
            ->assertMessage('Category deleted')
            ->assertCategoryNotExist(self::$firstCategory)
            ->assertCategoryNotExist(self::$secondCategory);
    }
}
