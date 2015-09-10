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
        sleep(1);

        // create root level page
        $pages->createPage()
            ->assertTitle('Create Page - Landing Pages - CMS')
            ->setTitle(self::$firstPage)
            ->waitForApiCall()
            ->savePage();

        // assert root level page created
        $pages->assertTitle(self::$firstPage . ' - Landing Pages - CMS')
            ->assertMessage('Page has been saved')
            ->assertPageExists(self::$firstPage);

        // create child page
        $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug])
            ->assertTitle(self::$firstPage . ' - Landing Pages - CMS')
            ->createChildPage()
            ->assertTitle('Create Page - Landing Pages - CMS')
            ->setTitle(self::$secondPage)
            ->waitForApiCall()
            ->savePage();

        // assert child page created
        $pages->assertTitle(self::$secondPage . ' - Landing Pages - CMS')
            ->assertMessage('Page has been saved')
            ->openTreeChildPages(self::$firstPage)
            ->assertPageExists(self::$secondPage)
            ->assertContainsChildPage(self::$firstPage, self::$secondPage)
            ->openPage(self::$secondPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug, self::$secondPageSlug])
            ->assertTitle(self::$secondPage . ' - Landing Pages - CMS');
    }

    /**
     * @depends testCreatePages
     */
    public function testSlugJsComponent()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');
        sleep(1);

        $page = $pages->openPage(self::$firstPage)
            ->assertCurrentSlugUrl([self::$firstPageSlug]);

        /**
         * check call slug API on title change
         */
        $page->editPage()
            ->clickUpdateRadioButton()
            ->setTitle(self::JS_COMPONENT_TEST_TITLE)
            ->waitForApiCall()
            ->savePage()
            ->assertCurrentSlugUrl([self::JS_COMPONENT_TEST_SLUG]);

        /**
         * if slug was changed it won't update on title changing
         */
        $page->editPage()
            ->clickUpdateRadioButton()
            ->setSlug(self::JS_COMPONENT_TEST_UPDATED_SLUG)
            ->setTitle(self::JS_COMPONENT_TEST_UPDATED_TITLE)
            ->waitForApiCall()
            ->savePage()
            ->assertCurrentSlugUrl([self::JS_COMPONENT_TEST_UPDATED_SLUG]);

        /**
         * rollback title
         */
        $page->editPage()
            ->clickUpdateRadioButton()
            ->setSlug(self::$firstPageSlug)
            ->setTitle(self::$firstPage)
            ->savePage();
    }

    /**
     * @depends testSlugJsComponent
     */
    public function testDragAndDrop()
    {
        /** @var Pages $pages */
        $pages = $this->login()->openPages('OroB2B\Bundle\CMSBundle');
        sleep(1);

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
         * move first Page after second Page
         *
         *      - Second Page
         *      - First Page
         */
        $pages->dragAndDropAfterTarget(self::$firstPage, self::$secondPage)
            ->assertMessage('You can not change order of root nodes');

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
        sleep(1);

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
        $pages->assertTitle('Landing Pages - CMS')
            ->assertMessage('Page deleted')
            ->assertPageNotExist(self::$firstPage)
            ->assertPageNotExist(self::$secondPage);
    }

    /**
     * {@inheritdoc}
     */
    public function login($userName = null, $password = null, $args = [])
    {
        return parent::login($userName, $password, ['url' => '/admin']);
    }
}
