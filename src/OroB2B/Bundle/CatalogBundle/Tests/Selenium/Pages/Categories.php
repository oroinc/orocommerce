<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class Categories extends AbstractPage
{
    const URL = 'catalog/category/';

    /**
     * @var string
     */
    protected $categorySelector = "//a[contains(., '%s')]/i";

    /**
     * @param \PHPUnit_Extensions_Selenium2TestCase $testCase
     * @param bool $redirect
     */
    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @param string $title
     * @return bool
     */
    public function assertCategoryExists($title)
    {
        $this->test->assertTrue($this->isElementPresent(sprintf($this->categorySelector, $title)));

        return $this;
    }

    /**
     * @param string $title
     * @return bool
     */
    public function assertCategoryNotExist($title)
    {
        $this->test->assertFalse($this->isElementPresent(sprintf($this->categorySelector, $title)));

        return $this;
    }

    /**
     * @param string $title
     * @return Category
     */
    public function openCategory($title)
    {
        $this->test->byXpath(sprintf($this->categorySelector, $title))->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Category($this->test);
    }

    /**
     * @return Category
     */
    public function createCategory()
    {
        $this->test->byXPath("//a[@title='Create Category']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Category($this->test);
    }
}
