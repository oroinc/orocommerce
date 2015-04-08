<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BRFPBundle:RequestStatus');
    }

    /**
     * Test index
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_index'));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request for proposal statuses', $result->getContent());
    }

    /**
     * Test create
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_create'));
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_rfp_request_status[name]']      = 'test';
        $form['orob2b_rfp_request_status[sortOrder]'] = '1000';
        $form['orob2b_rfp_request_status[translations][defaultLocale][en][label]'] = 'Test';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request status saved', $crawler->html());
    }

    /**
     * Test update
     *
     * @depend testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid('rfp-request-statuses-grid', ['rfp-request-statuses-grid[_filter][name][value]' => 'test']);

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_rfp_request_status[translations][defaultLocale][en][label]'] = 'Test_new';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request status saved', $crawler->html());

        return $id;
    }

    /**
     * Test view
     *
     * @depends testUpdate
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Test_new', $crawler->html());

        return $id;
    }

    /**
     * Test Delete
     *
     * @depends testView
     */
    public function testDelete($id)
    {
        $this->client->request('DELETE', $this->getUrl('orob2b_api_rfp_request_status_delete', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_view', ['id' => $id]));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Test_new', $crawler->html());
    }
}
