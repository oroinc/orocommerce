<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\CMSBundle\Entity\LoginPage;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class LoginPageControllerTest extends WebTestCase
{
    const TOP_CONTENT = 'html top content';
    const BOTTOM_CONTENT = 'html bottom content';
    const CSS = 'css styles';

    const TOP_CONTENT_UPDATE = 'html top content update';
    const BOTTOM_CONTENT_UPDATE = 'html bottom content update';
    const CSS_UPDATE = 'css styles update';

    const LOGIN_PAGE_ID = 1;

    /**
     * {@inheritdoc}
     */
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
        static::assertStringContainsString('cms-login-page-grid', $crawler->html());
        $this->assertEquals('Customer Login Pages', $crawler->filter('h1.oro-subtitle')->html());
        static::assertStringNotContainsString(
            'Create Login Page',
            $crawler->filter('div.title-buttons-container')->html()
        );
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_cms_loginpage_create'));

        $this->assertLoginPageSave($crawler, static::TOP_CONTENT, static::BOTTOM_CONTENT, static::CSS);

        /** @var LoginPage $page */
        $page = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCMSBundle:LoginPage')
            ->getRepository('OroCMSBundle:LoginPage')
            ->findOneBy(['id' => static::LOGIN_PAGE_ID]);
        $this->assertNotEmpty($page);

        return $page->getId();
    }

    /**
     * @param int $id
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_loginpage_update', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertLoginPageSave(
            $crawler,
            static::TOP_CONTENT_UPDATE,
            static::BOTTOM_CONTENT_UPDATE,
            static::CSS_UPDATE
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_cms_loginpage_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString(static::TOP_CONTENT_UPDATE, $html);
        static::assertStringContainsString(static::BOTTOM_CONTENT_UPDATE, $html);
    }

    /**
     * @param Crawler $crawler
     * @param string $topContent
     * @param string $bottomContent
     */
    private function assertLoginPageSave(Crawler $crawler, $topContent, $bottomContent)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_cms_login_page[topContent]'] = $topContent;
        $form['oro_cms_login_page[bottomContent]'] = $bottomContent;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        static::assertStringContainsString('Login form has been saved', $html);
        static::assertStringContainsString($topContent, $html);
        static::assertStringContainsString($bottomContent, $html);

        self::assertArrayNotHasKey('oro_cms_login_page[css]', $form);
    }
}
