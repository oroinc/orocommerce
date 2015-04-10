<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
 */
class RequestControllersTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_rfp_request_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('John', $result->getContent());
        $this->assertContains('Dow', $result->getContent());
        $this->assertContains('test_request@example.com', $result->getContent());
    }

    public function testView()
    {
        $response = $this->client->requestGrid(
            'rfp-requests-grid',
            [
                'rfp-requests-grid[_filter][firstName][value]' => 'John',
                'rfp-requests-grid[_filter][LastName][value]' => 'Dow'
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_view', ['id' => $result['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('John Dow - Requests for proposal - RFP', $result->getContent());
    }

    public function testInfo()
    {
        $response = $this->client->requestGrid(
            'rfp-requests-grid',
            [
                'rfp-requests-grid[_filter][firstName][value]' => 'John',
                'rfp-requests-grid[_filter][LastName][value]' => 'Dow'
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_info', ['id' => $result['id']])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 500);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_info', ['id' => $result['id']]),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('John', $result->getContent());
        $this->assertContains('Dow', $result->getContent());
        $this->assertContains('test_request@example.com', $result->getContent());
    }

    public function testChangeStatus()
    {
        $response = $this->client->requestGrid(
            'rfp-requests-grid',
            [
                'rfp-requests-grid[_filter][firstName][value]' => 'John',
                'rfp-requests-grid[_filter][LastName][value]' => 'Dow'
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_change_status', ['id' => $result['id']])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_change_status', ['id' => $result['id']]),
            ['_widgetContainer' => 'dialog']
        );

        $form = $crawler->selectButton('Update Request')->form();
        $form['orob2b_rfp_request_change_status[status]'] = 1;
        $form['orob2b_rfp_request_change_status[note]'] = 'Test Request Note';

        $params = $form->getPhpValues();
        $params['_widgetContainer'] = 'dialog';

        $this->client->request($form->getMethod(), $form->getUri(), $params);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Request for proposal status successfully changed", $result->getContent());
    }
}
