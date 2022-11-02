<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class LoginPageControllerTest extends WebTestCase
{
    private const TOP_CONTENT = 'html top content';
    private const BOTTOM_CONTENT = 'html bottom content';

    private const TOP_CONTENT_UPDATE = 'html top content update';
    private const BOTTOM_CONTENT_UPDATE = 'html bottom content update';

    private const LOGIN_PAGE_ID = 1;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_loginpage_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('cms-login-page-grid', $crawler->html());
        $this->assertEquals('Customer Login Pages', $crawler->filter('h1.oro-subtitle')->html());
        self::assertStringNotContainsString(
            'Create Login Page',
            $crawler->filter('div.title-buttons-container')->html()
        );
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_loginpage_create'));

        $this->assertLoginPageSave($crawler, self::TOP_CONTENT, self::BOTTOM_CONTENT);

        /** @var LoginPage $page */
        $page = $this->getContainer()->get('doctrine')
            ->getRepository(LoginPage::class)
            ->findOneBy(['id' => self::LOGIN_PAGE_ID]);
        $this->assertNotEmpty($page);

        return $page->getId();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_loginpage_update', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertLoginPageSave($crawler, self::TOP_CONTENT_UPDATE, self::BOTTOM_CONTENT_UPDATE);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_loginpage_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        self::assertStringContainsString(self::TOP_CONTENT_UPDATE, $html);
        self::assertStringContainsString(self::BOTTOM_CONTENT_UPDATE, $html);
    }

    private function assertLoginPageSave(Crawler $crawler, string $topContent, string $bottomContent): void
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_cms_login_page[topContent]'] = $topContent;
        $form['oro_cms_login_page[bottomContent]'] = $bottomContent;
        // we need to specify input_action form parameter manually because JS is not executed
        // and the value of this parameter have no correct redirect route data
        $form['input_action'] = '{"route":"oro_cms_loginpage_view","params":{"id":"$id"}}';

        $this->client->followRedirects();
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        self::assertStringContainsString('Login form has been saved', $html);
        self::assertStringContainsString($topContent, $html);
        self::assertStringContainsString($bottomContent, $html);

        self::assertArrayNotHasKey('oro_cms_login_page[css]', $form);
    }
}
