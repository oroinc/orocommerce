<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

/**
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

    const SLUG_MODE_NEW      = 'new';
    const SLUG_MODE_OLD      = 'old';
    const SLUG_MODE_REDIRECT = 'redirect';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass('OroCMSBundle:Page');
    }

    public function testGetChangedUrlsWhenSlugChanged()
    {
        $this->loadFixtures([LoadPageData::class]);
        $localization = $this->getContainer()->get('oro_locale.manager.localization')->getDefaultLocalization(false);

        /** @var Page $page */
        $page = $this->getReference(LoadPageData::PAGE_1);
        $page->setDefaultSlugPrototype('old-default-slug');
        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('old-english-slug')->setLocalization($localization);

        $page->addSlugPrototype($slugPrototype);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $page->getId()]));
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_cms_page']['slugPrototypesWithRedirect'] = [
            'slugPrototypes' => [
                'values' => [
                    'default' => 'default-slug',
                    'localizations' => [
                        $localization->getId() => ['value' => 'english-slug']
                    ]
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_cms_page_get_changed_urls', ['id' => $page->getId()]),
            $formValues
        );

        $expectedData = [
            'Default Value' => ['before' => '/old-default-slug', 'after' => '/default-slug'],
            'English (United States)' => ['before' => '/old-english-slug', 'after' => '/english-slug']
        ];

        $response = $this->client->getResponse();
        $this->assertJsonStringEqualsJsonString(json_encode($expectedData), $response->getContent());
    }

    public function testGetChangedUrlsWhenNoSlugChanged()
    {
        $this->loadFixtures([LoadPageData::class]);

        $page = $this->getReference(LoadPageData::PAGE_1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $page->getId()]));
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();

        $this->client->request(
            'POST',
            $this->getUrl('oro_cms_page_get_changed_urls', ['id' => $page->getId()]),
            $formValues
        );

        $response = $this->client->getResponse();
        $this->assertEquals('[]', $response->getContent());
    }

    public function testIndex()
    {
        $this->markTestSkipped('Due to BB-7566');
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals("Landing Pages", $crawler->filter('h1.oro-subtitle')->html());
        static::assertStringContainsString(
            "Please select a page on the left or create new one.",
            $crawler->filter('.content .text-center')->html()
        );
    }

    /**
     * @return int
     */
    public function testCreatePage()
    {
        $this->markTestSkipped('Due to BB-7566');
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
            self::DEFAULT_PAGE_SLUG_URL,
            self::UPDATED_DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::UPDATED_DEFAULT_PAGE_SLUG_URL,
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
            self::UPDATED_DEFAULT_PAGE_SLUG_URL,
            self::DEFAULT_PAGE_TITLE,
            self::UPDATED_DEFAULT_PAGE_SLUG_TEXT,
            self::UPDATED_DEFAULT_PAGE_SLUG_URL,
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
            self::UPDATED_DEFAULT_PAGE_SLUG_URL,
            self::DEFAULT_PAGE_TITLE,
            self::DEFAULT_PAGE_SLUG_TEXT,
            self::DEFAULT_PAGE_SLUG_URL,
            self::SLUG_MODE_REDIRECT,
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

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'DELETE',
                    'entityId' => $id,
                    'entityClass' => Page::class,
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_cms_page_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testValidationForLocalizedFallbackValues()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_create'));
        $form = $crawler->selectButton('Save and Close')->form();

        $bigStringValue = str_repeat('a', 256);
        $formValues = $form->getPhpValues();
        $formValues['oro_cms_page']['titles']['values']['default'] = $bigStringValue;
        $formValues['oro_cms_page']['slugPrototypesWithRedirect']['slugPrototypes'] = [
            'values' => ['default' => $bigStringValue]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            2,
            $crawler->filterXPath(
                "//li[contains(text(),'This value is too long. It should have 255 characters or less.')]"
            )->count()
        );
    }

    /**
     * @param string $title
     * @param string $slug
     * @return int
     */
    protected function assertCreate($title, $slug)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_page_create')
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_cms_page[title]']      = $title;
        $form['oro_cms_page[content]']    = sprintf('<p>Content for page:<strong>%s</strong></p>', $title);
        $form['oro_cms_page[slug][mode]'] = 'new';
        $form['oro_cms_page[slug][slug]'] = $slug;

        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString("Page has been saved", $crawler->html());

        return $this->getPageIdByUri($this->client->getRequest()->getRequestUri());
    }

    /**
     * @param string $title
     * @param string $url
     * @param string $newTitle
     * @param string $newSlug
     * @param string $newUrl
     * @param string $slugMode
     * @param int $id
     * @return int
     */
    protected function assertEdit($title, $url, $newTitle, $newSlug, $newUrl, $slugMode, $id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $id]));
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getValues();
        $this->assertEquals($title, $formValues['oro_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $title),
            $formValues['oro_cms_page[content]']
        );

        static::assertStringContainsString(
            "Redirect visitors from " . $url,
            $crawler->filter('.sub-item')->html()
        );

        $form['oro_cms_page[title]']      = $newTitle;
        $form['oro_cms_page[content]']    = sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle);

        switch ($slugMode) {
            case self::SLUG_MODE_NEW:
                $form['oro_cms_page[slug][mode]'] = $slugMode;
                $form['oro_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_OLD:
                $form['oro_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_REDIRECT:
                $form['oro_cms_page[slug][mode]']     = self::SLUG_MODE_NEW;
                $form['oro_cms_page[slug][slug]']     = $newSlug;
                $form['oro_cms_page[slug][redirect]'] = 1;
                break;
        }

        $form->setValues(['input_action' => 'save_and_stay']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString("Page has been saved", $crawler->html());

        $formValues = $form->getValues();
        $this->assertEquals($newTitle, $formValues['oro_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle),
            $formValues['oro_cms_page[content]']
        );

        static::assertStringContainsString(
            "Redirect visitors from " . $newUrl,
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
        $page = $this->entityManager->find('OroCMSBundle:Page', $id);

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
