<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
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

        $this->formatter = $this->getContainer()->get('oro_locale.formatter.name');
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
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertOrderSave($crawler, $this->getCurrentUser());

        return $this->getContainer()->get('doctrine')->getConnection()->lastInsertId();
    }

    /**
     * @depends testCreate
     *
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'orders-grid',
            [
                'orders-grid[_filter][owner][value]' => $this->getCurrentUser()->getFirstName()
                    . ' ' . $this->getCurrentUser()->getLastName()
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id      = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_update', ['id' => $result['id']])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertOrderSave($crawler, $this->getCurrentUser());

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     */
    public function testSubtotals($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form->getFormNode()->setAttribute('action', $this->getUrl('orob2b_order_subtotals', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertArrayHasKey('subtotal', $data['subtotals']);
    }

    /**
     * @depends testUpdate
     *
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
                'orob2b_order_type[owner]' => $owner->getId(),
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
