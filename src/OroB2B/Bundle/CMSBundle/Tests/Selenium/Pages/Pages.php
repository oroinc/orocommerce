<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class Pages extends AbstractPage
{
    const URL = 'admin/cms/page/';

    /** @var string */
    protected $page = '//a[contains(., "%s")]/i';

    /** @var string */
    protected $createButton = '//a[@title="Create Landing Page"]';

    /** @var string */
    protected $childPageClosed = '//a[contains(., "%s")]/parent::li[contains(@class,"jstree-closed")]';

    /** @var string */
    protected $childPageOpen = '//a[contains(., "%s")]/parent::*/i[contains(@class,"jstree-ocl")]';

    /** @var string */
    protected $childPageContains = '//a[contains(., "%s")]/parent::*/ul/li/a[contains(., "%s")]';

    /** @var string */
    protected $pageAfter = '//a[contains(., "%s")]/parent::*/following-sibling::*/ul/li/a[contains(., "%s")]';

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
     * @return Page
     */
    public function openPage($title)
    {
        $this->test->byXpath(sprintf($this->page, $title))->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Page($this->test);
    }

    /**
     * @return Page
     */
    public function createPage()
    {
        $this->test->byXPath($this->createButton)->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Page($this->test);
    }

    /**
     * @param string $title
     * @return $this
     */
    public function openTreeChildPages($title)
    {
        if ($this->isElementPresent(sprintf($this->childPageClosed, $title))) {
            $this->test->byXpath(sprintf($this->childPageOpen, $title))->click();
            sleep(1);
        }

        return $this;
    }

    /**
     * @param string $sourceTitle
     * @param string $targetTitle
     * @return $this
     */
    public function dragAndDrop($sourceTitle, $targetTitle)
    {
        $this->test->moveto($this->test->byXpath(sprintf($this->page, $sourceTitle)));
        $this->test->buttondown();
        $this->test->moveto($this->test->byXpath(sprintf($this->page, $targetTitle)));
        $this->test->buttonup();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $sourceTitle
     * @param string $targetTitle
     * @return $this
     */
    public function dragAndDropAfterTarget($sourceTitle, $targetTitle)
    {
        $this->test->moveto($this->test->byXpath(sprintf($this->page, $sourceTitle)));
        $this->test->buttondown();
        $this->test->moveto([
            'element' => $this->test->byXpath(sprintf($this->page, $targetTitle)),
            'xoffset' => 0,
            'yoffset' => 20,
        ]);
        $this->test->buttonup();

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function assertPageExists($title)
    {
        $this->test->assertTrue(
            $this->isElementPresent(sprintf($this->page, $title)),
            sprintf('Page %s does not exist', $title)
        );

        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function assertPageNotExist($title)
    {
        $this->test->assertFalse(
            $this->isElementPresent(sprintf($this->page, $title)),
            sprintf('Page %s exists', $title)
        );

        return $this;
    }

    /**
     * @param string $parentTitle
     * @param string $childTitle
     * @return $this
     */
    public function assertContainsChildPage($parentTitle, $childTitle)
    {
        $this->test->assertTrue(
            $this->isElementPresent(sprintf($this->childPageContains, $parentTitle, $childTitle)),
            sprintf('Page %s does not contain childPage %s', $parentTitle, $childTitle)
        );

        return $this;
    }

    /**
     * @param string $parentTitle
     * @param string $childTitle
     * @return $this
     */
    public function assertNotContainChildPage($parentTitle, $childTitle)
    {
        $this->test->assertFalse(
            $this->isElementPresent(sprintf($this->childPageContains, $parentTitle, $childTitle)),
            sprintf('Page %s contains childPage %s', $parentTitle, $childTitle)
        );

        return $this;
    }

    /**
     * @param string $beforeTitle
     * @param string $afterTitle
     * @return $this
     */
    public function assertPageAfter($beforeTitle, $afterTitle)
    {
        $this->test->assertFalse(
            $this->isElementPresent(sprintf($this->pageAfter, $beforeTitle, $afterTitle)),
            sprintf('Page %s does not rendered after page %s', $afterTitle, $beforeTitle)
        );

        return $this;
    }
}
