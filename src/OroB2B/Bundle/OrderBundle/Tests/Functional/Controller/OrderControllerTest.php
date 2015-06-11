<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Symfony\Component\DomCrawler\Crawler;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    /**
     * @var NameFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );

        $this->formatter = $this->getContainer()->get('oro_locale.twig.name');
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_order_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertOrderSave($crawler, $this->getCurrentUser());

        return $this->getContainer()->get('doctrine')->getConnection()->lastInsertId();
    }

    /**
     * @depends testCreate
     * @param int $id
     *
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $newOwner = $this->getReference('order_simple_user');
        $this->assertOrderSave($crawler, $newOwner);

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
            $this->getUrl('orob2b_order_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertViewPage($crawler, $this->getCurrentUser());
    }

    /**
     * @param Crawler $crawler
     * @param User    $owner
     */
    protected function assertOrderSave(Crawler $crawler, User $owner)
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_order_type[owner]' => $owner,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Order has been saved', $html);
        $this->assertViewPage($crawler, $owner);
    }

    /**
     * @param Crawler $crawler
     * @param User    $owner
     */
    public function assertViewPage(Crawler $crawler, User $owner)
    {
        $html = $crawler->filter('.user-info-state')->html();
        $this->assertContains($this->formatter->format($owner), $html);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('security.context')->getToken()->getUser();
    }
}
