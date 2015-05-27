<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Page extends AbstractPageEntity
{
    const SLUG_MODE_NEW = 'new';
    const SLUG_MODE_OLD = 'old';

    /** @var string */
    protected $childPageButton = '//a[@title="Create Child page"]';

    /** @var string */
    protected $editButton = '//a[@title="Edit Page"]';

    /** @var string */
    protected $deleteButton = '//a[@title="Delete Page"]';

    /** @var string */
    protected $deleteConfirmButton = '//a[contains(., "Yes, Delete")]';

    /** @var string */
    protected $currentSlugUrl = '//label[contains(., "Current slug")]/parent::div/div[contains(., "%s")]';

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $element = $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_title')]");
        $element->clear();
        $element->value($title);

        return $this;
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $element = $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_slug')]");
        $element->clear();
        $element->value($slug);

        return $this;
    }

    /**
     * @param string $slugMode
     * @return $this
     */
    public function setSlugMode($slugMode)
    {
        switch ($slugMode) {
            case self::SLUG_MODE_NEW:
                $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_mode_1')]")->click();
                break;
            case self::SLUG_MODE_OLD:
                $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_mode_0')]")->click();
                break;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setSlugRedirect()
    {
        $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_redirect')]")->click();

        return $this;
    }

    /**
     * @return Page
     */
    public function createChildPage()
    {
        $this->test->byXPath($this->childPageButton)->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        sleep(1);

        return new Page($this->test);
    }

    public function deletePage()
    {
        $this->test->byXPath($this->deleteButton)->click();
        $this->test->byXPath($this->deleteConfirmButton)->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        sleep(1);
    }

    /**
     * @return $this
     */
    public function savePage()
    {
        $this->save();
        $this->waitPageToLoad();
        $this->waitForAjax();
        sleep(1);

        return $this;
    }

    /**
     * @return $this
     */
    public function assertDeleteAllowed()
    {
        $this->test->assertTrue(
            $this->isElementPresent($this->deleteButton),
            'Delete button %s does not exist'
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function assertDeleteNotAllowed()
    {
        $this->test->assertFalse(
            $this->isElementPresent($this->deleteButton),
            'Delete button %s exists'
        );

        return $this;
    }

    /**
     * @param string[] $urls
     * @return $this
     */
    public function assertCurrentSlugUrl(array $urls)
    {
        $slugUrl = '/' . implode('/', $urls);

        $this->test->assertTrue(
            $this->isElementPresent(sprintf($this->currentSlugUrl, $slugUrl)),
            sprintf('Page does not contain current slug url %s', $slugUrl)
        );

        return $this;
    }

    /**
     * @return Page
     */
    public function editPage()
    {
        $this->test->byXPath($this->editButton)->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        sleep(1);

        return new Page($this->test);
    }

    public function clickLeaveAsIsRadioButton()
    {
        $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_mode_0')]")->click();

        return $this;
    }

    public function clickUpdateRadioButton()
    {
        $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_mode_1')]")->click();

        return $this;
    }

    public function assertSlugInputValue($slug)
    {
        $this->test->assertEquals(
            $this->test->byXpath("//input[starts-with(@id,'orob2b_cms_page_slug_slug')]")->value(),
            $slug,
            sprintf('Slug input does not contain slug %s', $slug)
        );

        return $this;
    }

    public function waitForApiCall()
    {
        sleep(1);
        $this->waitForAjax();
        sleep(1);

        return $this;
    }
}
