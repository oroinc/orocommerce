<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CMSBundle\Entity\Page;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PageControllerTest extends WebTestCase
{
    const DEFAULT_PAGE_TITLE                = 'Page Title';
    const DEFAULT_PAGE_SLUG_TEXT            = 'page-title';
    const DEFAULT_PAGE_SLUG_URL             = '/page-title';
    const UPDATED_DEFAULT_PAGE_TITLE        = 'Updated Page Title';
    const UPDATED_DEFAULT_PAGE_SLUG_TEXT    = 'updated-page-title';
    const UPDATED_DEFAULT_PAGE_SLUG_URL     = '/updated-page-title';
    const DEFAULT_SUBPAGE_TITLE             = 'Subpage Title';
    const DEFAULT_SUBPAGE_SLUG_TEXT         = 'subpage-title';
    const DEFAULT_SUBPAGE_SLUG_URL          = '/page-title/subpage-title';
    const UPDATED_DEFAULT_SUBPAGE_TITLE     = 'Updated Subpage Title';
    const UPDATED_DEFAULT_SUBPAGE_SLUG_TEXT = 'updated-subpage-title';
    const UPDATED_DEFAULT_SUBPAGE_SLUG_URL  = '/page-title/updated-subpage-title';

    const SLUG_MODE_NEW      = 'new';
    const SLUG_MODE_OLD      = 'old';
    const SLUG_MODE_REDIRECT = 'redirect';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BCMSBundle:Page');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_page_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals("Pages", $crawler->filter('h1.oro-subtitle')->html());
        $this->assertContains(
            "Please select a page on the left or create new one.",
            $crawler->filter('.content .text-center')->html()
        );
    }

    /**
     * @return int
     */
    public function testCreatePage()
    {
        return $this->assertCreate(self::DEFAULT_PAGE_TITLE, self::DEFAULT_PAGE_SLUG_TEXT);
    }

    /**
     * @depends testCreatePage
     * @param int $id
     * @return int
     */
    public function testEditPageWithNewSlug($id)
    {
        $this->assertSlugs(self::DEFAULT_PAGE_SLUG_URL, array(), $id);

        return $this->assertEdit(
            self::DEFAULT_PAGE_TITLE,
            self::DEFAULT_PAGE_SLUG_TEXT,
            self::UPDATED_DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::SLUG_MODE_NEW,
            $id
        );
    }

    /**
     * @depends testEditPageWithNewSlug
     * @param int $id
     * @return int
     */
    public function testEditPageWithOldSlug($id)
    {
        $this->assertSlugs(self::UPDATED_DEFAULT_PAGE_SLUG_URL, array(), $id);

        return $this->assertEdit(
            self::UPDATED_DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::SLUG_MODE_OLD,
            $id
        );
    }

    /**
     * @depends testEditPageWithOldSlug
     * @param int $id
     * @return int
     */
    public function testEditPageWithNewSlugAndRedirect($id)
    {
        $this->assertSlugs(self::UPDATED_DEFAULT_PAGE_SLUG_URL, array(), $id);

        return $this->assertEdit(
            self::DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::DEFAULT_PAGE_TITLE,
            self::DEFAULT_PAGE_SLUG_TEXT,
            self::SLUG_MODE_REDIRECT,
            $id
        );
    }

    /**
     * @depends testCreatePage
     * @param int $id
     * @return int
     */
    public function testCreateSubPage($id)
    {
        return $this->assertCreate(self::DEFAULT_SUBPAGE_TITLE, self::DEFAULT_SUBPAGE_SLUG_TEXT, $id);
    }

    /**
     * @depends testCreateSubPage
     * @param int $id
     * @return int
     */
    public function testEditSubPage($id)
    {
        $this->assertSlugs(self::DEFAULT_SUBPAGE_SLUG_URL, array(), $id);

        return $this->assertEdit(
            self::DEFAULT_SUBPAGE_TITLE,
            self::DEFAULT_PAGE_SLUG_TEXT . '/' . self::DEFAULT_SUBPAGE_SLUG_TEXT,
            self::UPDATED_DEFAULT_SUBPAGE_TITLE,
            self::UPDATED_DEFAULT_SUBPAGE_SLUG_TEXT,
            self::SLUG_MODE_NEW,
            $id
        );
    }

    /**
     * @depends testEditPageWithNewSlugAndRedirect
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->assertSlugs(self::DEFAULT_PAGE_SLUG_URL, array(self::UPDATED_DEFAULT_PAGE_SLUG_URL), $id);

        $page = $this->entityManager->find('OroB2BCMSBundle:Page', $id);
        $children = $page->getChildPages();

        $this->client->request('DELETE', $this->getUrl('orob2b_api_cms_delete_page', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_cms_page_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);

        // Check all child page are deleted
        foreach ($children as $childPage) {
            $this->assertNull($this->entityManager->find('OroB2BCMSBundle:Page', $childPage->getId()));
        }
    }

    /**
     * @param string $title
     * @param string $slug
     * @param int $parentId
     * @return int
     */
    protected function assertCreate($title, $slug, $parentId = null)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_cms_page_create', ['id' => $parentId])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_cms_page[title]']      = $title;
        $form['orob2b_cms_page[content]']    = sprintf('<p>Content for page:<strong>%s</strong></p>', $title);
        $form['orob2b_cms_page[slug][mode]'] = 'new';
        $form['orob2b_cms_page[slug][slug]'] = $slug;

        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Page has been saved", $crawler->html());

        return $this->getPageIdByUri($this->client->getRequest()->getRequestUri());
    }

    /**
     * @param string $title
     * @param string $slug
     * @param string $newTitle
     * @param string $newSlug
     * @param string $slugMode
     * @param int $id
     * @return int
     */
    protected function assertEdit($title, $slug, $newTitle, $newSlug, $slugMode, $id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_page_update', ['id' => $id]));
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getValues();
        $this->assertEquals($title, $formValues['orob2b_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $title),
            $formValues['orob2b_cms_page[content]']
        );

        $this->assertContains(
            "Redirect visitors from /" . $slug,
            $crawler->filter('.sub-item')->html()
        );

        $form['orob2b_cms_page[title]']      = $newTitle;
        $form['orob2b_cms_page[content]']    = sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle);

        switch ($slugMode) {
            case self::SLUG_MODE_NEW:
                $form['orob2b_cms_page[slug][mode]'] = $slugMode;
                $form['orob2b_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_OLD:
                $form['orob2b_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_REDIRECT:
                $form['orob2b_cms_page[slug][mode]']     = self::SLUG_MODE_NEW;
                $form['orob2b_cms_page[slug][slug]']     = $newSlug;
                $form['orob2b_cms_page[slug][redirect]'] = 1;
                break;
        }

        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Page has been saved", $crawler->html());

        $formValues = $form->getValues();
        $this->assertEquals($newTitle, $formValues['orob2b_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle),
            $formValues['orob2b_cms_page[content]']
        );

        $this->assertContains(
            "Redirect visitors from /" . $newSlug,
            $crawler->filter('.sub-item')->html()
        );

        return $id;
    }

    /**
     * @param string $expectedCurrentSlug
     * @param string[] $expectedRelatedSlugs
     * @param int $id
     * @return int
     */
    protected function assertSlugs($expectedCurrentSlug, array $expectedRelatedSlugs, $id)
    {
        /** @var Page $page */
        $page = $this->entityManager->find('OroB2BCMSBundle:Page', $id);

        $this->assertEquals($expectedCurrentSlug, $page->getCurrentSlug()->getUrl());

        $relatedSlugs = [];

        foreach ($page->getRelatedSlugs() as $slug) {
            $relatedSlugs[] = $slug->getUrl();
        }

        $this->assertEquals($expectedRelatedSlugs, $relatedSlugs);

        return $id;
    }

    /**
     * @param string $uri
     * @return int
     */
    protected function getPageIdByUri($uri)
    {
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($uri);

        $this->assertArrayHasKey('id', $parameters);

        return $parameters['id'];
    }
}
