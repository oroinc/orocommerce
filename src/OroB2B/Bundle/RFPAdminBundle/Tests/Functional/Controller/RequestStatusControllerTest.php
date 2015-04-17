<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RequestStatusControllerTest extends WebTestCase
{
    const OLD_NAME = 'test';
    const OLD_LABEL = 'Test';
    const NEW_LABEL = 'New Test';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * Test index
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_index'));

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request For Proposal Statuses', $result->getContent());
    }

    /**
     * Test create
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_create'));
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_rfp_request_status[name]']      = self::OLD_NAME;
        $form['orob2b_rfp_request_status[sortOrder]'] = '1000';
        $form['orob2b_rfp_request_status[translations][defaultLocale][en][label]'] = self::OLD_LABEL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request Status saved', $crawler->html());
    }

    /**
     * Test update
     *
     * @depend testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'rfp-request-statuses-grid',
            ['rfp-request-statuses-grid[_filter][name][value]' => self::OLD_NAME]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);
        $id = $result['id'];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_rfp_request_status[translations][defaultLocale][en][label]'] = self::NEW_LABEL;
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request Status saved', $crawler->html());

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
        $this->assertContains(self::NEW_LABEL, $crawler->html());

        return $id;
    }
}
