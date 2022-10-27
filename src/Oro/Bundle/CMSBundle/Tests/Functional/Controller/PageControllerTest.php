<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PageControllerTest extends WebTestCase
{
    private const DEFAULT_PAGE_TITLE = 'Page Title';
    private const DEFAULT_PAGE_SLUG_TEXT = 'page-title';
    private const DEFAULT_PAGE_SLUG_URL = '/page-title';
    private const UPDATED_DEFAULT_PAGE_TITLE = 'Updated Page Title';
    private const UPDATED_DEFAULT_PAGE_SLUG_TEXT = 'updated-page-title';
    private const UPDATED_DEFAULT_PAGE_SLUG_URL = '/updated-page-title';

    private const SLUG_MODE_NEW = 'new';
    private const SLUG_MODE_OLD = 'old';
    private const SLUG_MODE_REDIRECT = 'redirect';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(Page::class);
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
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedData, JSON_THROW_ON_ERROR),
            $response->getContent()
        );
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
        $this->assertEquals('Landing Pages', $crawler->filter('h1.oro-subtitle')->html());
        self::assertStringContainsString(
            'Please select a page on the left or create new one.',
            $crawler->filter('.content .text-center')->html()
        );
    }

    public function testCreatePage(): int
    {
        $this->markTestSkipped('Due to BB-7566');
        return $this->assertCreate(self::DEFAULT_PAGE_TITLE, self::DEFAULT_PAGE_SLUG_TEXT);
    }

    /**
     * @depends testCreatePage
     */
    public function testEditPageWithNewSlug(int $id): int
    {
        $this->assertSlugs(self::DEFAULT_PAGE_SLUG_URL, [], $id);

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
     */
    public function testEditPageWithOldSlug(int $id): int
    {
        $this->assertSlugs(self::UPDATED_DEFAULT_PAGE_SLUG_URL, [], $id);

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
     */
    public function testEditPageWithNewSlugAndRedirect(int $id): int
    {
        $this->assertSlugs(self::UPDATED_DEFAULT_PAGE_SLUG_URL, [], $id);

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
     */
    public function testDelete(int $id)
    {
        $this->assertSlugs(self::DEFAULT_PAGE_SLUG_URL, [self::UPDATED_DEFAULT_PAGE_SLUG_URL], $id);

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
            self::jsonToArray($this->client->getResponse()->getContent())
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

    private function assertCreate(string $title, string $slug): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_page_create')
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_cms_page[title]'] = $title;
        $form['oro_cms_page[content]'] = sprintf('<p>Content for page:<strong>%s</strong></p>', $title);
        $form['oro_cms_page[slug][mode]'] = 'new';
        $form['oro_cms_page[slug][slug]'] = $slug;

        $form->setValues(['input_action' => '{"route":"oro_cms_page_update","params":{"id":"$id"}}']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Page has been saved', $crawler->html());

        return $this->getPageIdByUri($this->client->getRequest()->getRequestUri());
    }

    private function assertEdit(
        string $title,
        string $url,
        string $newTitle,
        string $newSlug,
        string $newUrl,
        string $slugMode,
        int $id
    ): int {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_page_update', ['id' => $id]));
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getValues();
        $this->assertEquals($title, $formValues['oro_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $title),
            $formValues['oro_cms_page[content]']
        );

        self::assertStringContainsString(
            'Redirect visitors from ' . $url,
            $crawler->filter('.sub-item')->html()
        );

        $form['oro_cms_page[title]'] = $newTitle;
        $form['oro_cms_page[content]'] = sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle);

        switch ($slugMode) {
            case self::SLUG_MODE_NEW:
                $form['oro_cms_page[slug][mode]'] = $slugMode;
                $form['oro_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_OLD:
                $form['oro_cms_page[slug][slug]'] = $newSlug;
                break;
            case self::SLUG_MODE_REDIRECT:
                $form['oro_cms_page[slug][mode]'] = self::SLUG_MODE_NEW;
                $form['oro_cms_page[slug][slug]'] = $newSlug;
                $form['oro_cms_page[slug][redirect]'] = 1;
                break;
        }

        $form->setValues(['input_action' => '{"route":"oro_cms_page_update","params":{"id":"$id"}}']);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Page has been saved', $crawler->html());

        $formValues = $form->getValues();
        $this->assertEquals($newTitle, $formValues['oro_cms_page[title]']);
        $this->assertEquals(
            sprintf('<p>Content for page:<strong>%s</strong></p>', $newTitle),
            $formValues['oro_cms_page[content]']
        );

        self::assertStringContainsString(
            'Redirect visitors from ' . $newUrl,
            $crawler->filter('.sub-item')->html()
        );

        return $id;
    }

    private function assertSlugs(string $expectedCurrentSlug, array $expectedRelatedSlugs, int $id): void
    {
        /** @var Page $page */
        $page = $this->entityManager->find(Page::class, $id);

        $this->assertEquals($expectedCurrentSlug, $page->getCurrentSlug()->getUrl());

        $relatedSlugs = [];
        foreach ($page->getRelatedSlugs() as $slug) {
            $relatedSlugs[] = $slug->getUrl();
        }

        $this->assertEquals($expectedRelatedSlugs, $relatedSlugs);
    }

    private function getPageIdByUri(string $uri): int
    {
        $router = $this->getContainer()->get('router');
        $parameters = $router->match($uri);

        $this->assertArrayHasKey('id', $parameters);

        return $parameters['id'];
    }
}
