<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class Categories extends AbstractPage
{
    const URL = 'catalog/category/';

    /** @var string */
    protected $category = '//a[contains(., "%s")]/i';

    /** @var string */
    protected $createButton = '//a[@title="Create Category"]';

    /** @var string */
    protected $subcategoryShow = '//a[contains(., "%s")]/parent::*/i';

    /** @var string */
    protected $subcategoryContains = '//a[contains(., "%s")]/parent::*/ul/li/a[contains(., "%s")]';

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
     * @return Category
     */
    public function openCategory($title)
    {
        $this->test->byXpath(sprintf($this->category, $title))->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Category($this->test);
    }

    /**
     * @return Category
     */
    public function createCategory()
    {
        $this->test->byXPath($this->createButton)->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Category($this->test);
    }

    /**
     * @param string $title
     * @return $this
     */
    public function clickTreeSubcategories($title)
    {
        $this->test->byXpath(sprintf($this->subcategoryShow, $title))->click();

        return $this;
    }

    /**
     * @param string $sourceTitle
     * @param string $targetTitle
     * @return $this
     */
    public function dragAndDrop($sourceTitle, $targetTitle)
    {
        $this->test->moveto($this->test->byXpath(sprintf($this->category, $sourceTitle)));
        $this->test->buttondown();
        $this->test->moveto($this->test->byXpath(sprintf($this->category, $targetTitle)));
        $this->test->buttonup();

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function assertCategoryExists($title)
    {
        $this->test->assertTrue(
            $this->isElementPresent(sprintf($this->category, $title)),
            sprintf('Category %s does not exist', $title)
        );

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function assertCategoryNotExist($title)
    {
        $this->test->assertFalse(
            $this->isElementPresent(sprintf($this->category, $title)),
            sprintf('Category %s exists', $title)
        );

        return $this;
    }

    /**
     * @param string $parentTitle
     * @param string $childTitle
     * @return $this
     */
    public function assertContainsSubcategory($parentTitle, $childTitle)
    {
        $this->test->assertTrue(
            $this->isElementPresent(sprintf($this->subcategoryContains, $parentTitle, $childTitle)),
            sprintf('Category %s does not contain subcategory %s', $parentTitle, $childTitle)
        );

        return $this;
    }

    /**
     * @param string $parentTitle
     * @param string $childTitle
     * @return $this
     */
    public function assertNotContainSubcategory($parentTitle, $childTitle)
    {
        $this->test->assertFalse(
            $this->isElementPresent(sprintf($this->subcategoryContains, $parentTitle, $childTitle)),
            sprintf('Category %s contains subcategory %s', $parentTitle, $childTitle)
        );

        return $this;
    }
}
