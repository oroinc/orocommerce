<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

use OroB2B\Bundle\CatalogBundle\Tests\Selenium\Pages\Categories;

class CategoriesTest extends Selenium2TestCase
{
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

    public function testCreateFirstLevelCategory()
    {
        /** @var Categories $categories */
        $categories = $this->login()->openCategories('OroB2B\Bundle\CatalogBundle');
        $categories->assertTitle('Categories - Catalog Management');

        $categories->createCategory()
            ->assertTitle('Create Category - Categories - Catalog Management')
            ->setDefaultTitle(self::$firstCategory)
            ->save();

        $categories->assertTitle('Categories - Catalog Management')
            ->assertMessage('Category saved')
            ->assertCategoryExists(self::$firstCategory);

        $categories->openCategory(self::$firstCategory)
            ->assertTitle(self::$firstCategory . ' - Edit - Categories - Catalog Management');

        return $categories;
    }

    /**
     * @depends testCreateFirstLevelCategory
     */
    public function testCreateSecondLevelCategory()
    {
        /** @var Categories $categories */
        $categories = $this->login()->openCategories('OroB2B\Bundle\CatalogBundle');

    }
}
