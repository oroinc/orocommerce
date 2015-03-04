<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

class Category extends AbstractPageEntity
{
    /**
     * @var \PHPUnit_Extensions_Selenium2TestCase_Element
     */
    protected $defaultTitle;

    /**
     * {@inheritdoc}
     */
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);

        $this->defaultTitle = $this->test->byId('orob2b_catalog_category_titles_values_default');
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setDefaultTitle($title)
    {
        $this->defaultTitle->value($title);

        return $this;
    }

    public function createSubcategory()
    {
        $this->test->byXPath("//a[@title='Create Subcategory']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Category($this->test);
    }
}
