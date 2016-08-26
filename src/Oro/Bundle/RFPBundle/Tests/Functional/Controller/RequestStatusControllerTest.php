<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;

/**
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('rfp-request-statuses-grid', $crawler->html());
        $this->assertContains('RFQ Statuses', $result->getContent());
    }

    /**
     * @return int
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

        /** @var RequestStatus $status */
        $status = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroRFPBundle:RequestStatus')
            ->getRepository('OroRFPBundle:RequestStatus')
            ->findOneBy(['name' => self::OLD_NAME]);
        $this->assertNotEmpty($status);

        return $status->getId();
    }

    /**
     * @param $id int
     * @return int
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_status_update', [
            'id' => $id
        ]));

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
     * @param int $id
     * @return int
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
