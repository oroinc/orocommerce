<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LoginPageControllerTest extends WebTestCase
{
    const TOP_CONTENT = 'html top content';
    const BOTTOM_CONTENT = 'html bottom content';
    const CSS = 'css styles';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_loginpage_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals("Frontend Login Pages", $crawler->filter('h1.oro-subtitle')->html());
        $this->assertNotContains('Create Login Page', $crawler->filter('div.title-buttons-container')->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_cms_loginpage_create'));

        $form = $crawler->selectButton('Save and Close')->form();

        $form['orob2b_cms_login_page[topContent]'] = static::TOP_CONTENT;
        $form['orob2b_cms_login_page[bottomContent]'] = static::BOTTOM_CONTENT;
        $form['orob2b_cms_login_page[css]'] = static::CSS;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Login form has been saved', $html);
        $this->assertContains(self::TOP_CONTENT, $html);
        $this->assertContains(self::INVENTORY_STATUS, $html);
        $this->assertContains(self::VISIBILITY, $html);
        $this->assertContains(self::STATUS, $html);
    }
}
