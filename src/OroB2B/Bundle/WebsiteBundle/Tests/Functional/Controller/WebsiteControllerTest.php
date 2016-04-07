<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class WebsiteControllerTest extends WebTestCase
{
    const WEBSITE_TEST_NAME = 'OroCRM';
    const WEBSITE_UPDATED_TEST_NAME = 'OroCommerce';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Websites', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_website_type' => [
                '_token' => $form['orob2b_website_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_TEST_NAME,
                'fallback' => true,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertWebsiteSaved($crawler, self::WEBSITE_TEST_NAME);

        $result = $this->getWebsiteDataByName(self::WEBSITE_TEST_NAME);

        return $result['id'];
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_update', ['id' => $id]));
        $html = $crawler->html();

        $this->assertContains(self::WEBSITE_TEST_NAME, $html);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_website_type' => [
                '_token' => $form['orob2b_website_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_UPDATED_TEST_NAME,
                'fallback' => true,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertWebsiteSaved($crawler, self::WEBSITE_UPDATED_TEST_NAME);

        $result = $this->getWebsiteDataByName(self::WEBSITE_UPDATED_TEST_NAME);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getWebsiteDataByName($name)
    {
        $response = $this->client->requestGrid(
            'websites-grid',
            [
                'websites-grid[_filter][name][value]' => $name,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $result = reset($result['data']);

        $this->assertNotEmpty($result);

        return $result;
    }

    /**
     * @param Crawler $crawler
     * @param string $websiteName
     */
    protected function assertWebsiteSaved(Crawler $crawler, $websiteName)
    {
        $html = $crawler->html();
        $this->assertContains('Website has been saved', $html);
        $this->assertContains($websiteName, $html);
        $this->assertEquals($websiteName, $crawler->filter('h1.user-name')->html());
    }
}
