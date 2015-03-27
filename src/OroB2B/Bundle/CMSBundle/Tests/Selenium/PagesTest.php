<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

use OroB2B\Bundle\CMSBundle\Tests\Selenium\Pages\Pages;

class PagesTest extends Selenium2TestCase
{
    const JS_COMPONENT_TEST_TITLE         = 'Test title';
    const JS_COMPONENT_TEST_UPDATED_TITLE = 'Test title changed';
    const JS_COMPONENT_TEST_SLUG          = 'test-title';
    const JS_COMPONENT_TEST_UPDATED_SLUG  = 'test-title-updated';

    /**
     * @var string
     */
    protected static $firstPage;

    /**
     * @var string
     */
    protected static $secondPage;

    /**
     * @var string
     */
    protected static $firstPageSlug;

    /**
     * @var string
     */
    protected static $secondPageSlug;

    public static function setUpBeforeClass()
    {
        $suffix = uniqid();

        self::$firstPage         = 'Root page ' . $suffix;
        self::$firstPageSlug     = 'root-page-' . $suffix;
        self::$secondPage        = 'Test page ' . $suffix;
        self::$secondPageSlug    = 'test-page-' . $suffix;
    }

    public function testCreatePages()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');

        // create root level page
        $pages->createPage()
            ->assertTitle('Create Page - Pages - CMS')
            ->setTitle(self::$firstPage)
            ->setSlug(self::$firstPageSlug)
            ->savePage();

        // assert root level page created
        $pages->assertTitle(self::$firstPage . ' - Pages - CMS')
            ->assertMessage('Page has been saved')
            ->assertPageExists(self::$firstPage);

        // create child page
        $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug])
            ->assertTitle(self::$firstPage . ' - Pages - CMS')
            ->createChildPage()
            ->assertTitle('Create Page - Pages - CMS')
            ->setTitle(self::$secondPage)
            ->setSlug(self::$secondPageSlug)
            ->savePage();

        // assert child page created
        $pages->assertTitle(self::$secondPage . ' - Pages - CMS')
            ->assertMessage('Page has been saved')
            ->openTreeChildPages(self::$firstPage)
            ->assertPageExists(self::$secondPage)
            ->assertContainsChildPage(self::$firstPage, self::$secondPage)
            ->openPage(self::$secondPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug, self::$secondPageSlug])
            ->assertTitle(self::$secondPage . ' - Pages - CMS');
    }

    /**
     * @depends testCreatePages
     */
    public function testSlugJsComponent()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');

        $page = $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug])
            ->editPage();

        /**
         * preconditions
         *
         * Leave as is radio button is selected
         * Update radio button is not selected
         * Slug input disabled
         * Redirect checkbox disabled
         */
        $page->assertLeaveAsIsRadioButtonSelected()
            ->assertUpdateRadioButtonNotSelected()
            ->assertSlugInputDisabled()
            ->assertRedirectCheckboxDisabled();

        /**
         * click on Update slug radio button
         *
         * Leave as is radio button is notselected
         * Update radio button is selected
         * Slug input enabled
         * Redirect checkbox enabled
         */
        $page->clickUpdateRadioButton()
            ->assertLeaveAsIsRadioButtonNotSelected()
            ->assertUpdateRadioButtonSelected()
            ->assertSlugInputEnabled()
            ->assertRedirectCheckboxEnabled();

        /**
         * click on Update slug radio button
         *
         * Leave as is radio button is selected
         * Update radio button is not selected
         * Slug input disabled
         * Redirect checkbox disabled
         */
        $page->clickLeaveAsIsRadioButton()
            ->assertLeaveAsIsRadioButtonSelected()
            ->assertUpdateRadioButtonNotSelected()
            ->assertSlugInputDisabled()
            ->assertRedirectCheckboxDisabled();

        /**
         * check call slug API on title change
         */
        $page->clickUpdateRadioButton()
            ->setTitle(self::JS_COMPONENT_TEST_TITLE)
            ->waitForApiCall()
            ->assertSlugInputValue(self::JS_COMPONENT_TEST_SLUG);

        /**
         * if slug was changed it won't update on title changing
         */
        $page->setSlug(self::JS_COMPONENT_TEST_UPDATED_SLUG)
            ->setTitle(self::JS_COMPONENT_TEST_UPDATED_TITLE)
            ->waitForApiCall()
            ->assertSlugInputValue(self::JS_COMPONENT_TEST_UPDATED_SLUG);
    }

    /**
     * @depends testSlugJsComponent
     */
    public function testDragAndDrop()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');

        /**
         * preconditions
         *
         *      - First page
         *          - Second page
         */
        $pages->assertPageExists(self::$firstPage)
            ->assertPageExists(self::$secondPage)
            ->assertContainsChildPage(self::$firstPage, self::$secondPage);

        /**
         * move second Page to root level
         *
         *      - First Page
         *      - Second Page
         */
        $pages->dragAndDropAfterTarget(self::$secondPage, self::$firstPage)
            ->assertNotContainChildPage(self::$firstPage, self::$secondPage)
            ->assertPageAfter(self::$secondPage, self::$firstPage);

        $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug]);

        $pages->openPage(self::$secondPage)
            ->assertCurrentSlugUrl([self::$secondPageSlug]);

        /**
         * move first Page after first Page
         *
         *      - Second Page
         *      - First Page
         */
        $pages->dragAndDropAfterTarget(self::$firstPage, self::$secondPage)
            ->assertPageAfter(self::$secondPage, self::$firstPage);

        /**
         * move first Page to second Page
         *
         *      - Second Page
         *          - First Page
         */
        $pages->dragAndDrop(self::$firstPage, self::$secondPage)
            ->openTreeChildPages(self::$secondPage)
            ->assertContainsChildPage(self::$secondPage, self::$firstPage);

        $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$secondPageSlug, self::$firstPageSlug]);

        $pages->openPage(self::$secondPage)
            ->assertCurrentSlugUrl([self::$secondPageSlug]);
    }

    /**
     * @depends testDragAndDrop
     */
    public function testDeletePages()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');

        /**
         * preconditions
         *
         *      - Second Page
         *          - First Page
         */
        $pages->assertPageExists(self::$secondPage)
            ->openTreeChildPages(self::$secondPage)
            ->assertPageExists(self::$firstPage)
            ->assertContainsChildPage(self::$secondPage, self::$firstPage);

        // delete second Page, first should be removed automatically
        $pages->openPage(self::$secondPage)
            ->assertDeleteAllowed()
            ->deletePage();

        // assert Pages removed
        $pages->assertTitle('Pages - CMS')
            ->assertMessage('Page deleted')
            ->assertPageNotExist(self::$firstPage)
            ->assertPageNotExist(self::$secondPage);
    }
}
