<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
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
        $this->assertContains(LoadRequestData::FIRST_NAME, $result->getContent());
        $this->assertContains(LoadRequestData::LAST_NAME, $result->getContent());
        $this->assertContains(LoadRequestData::EMAIL, $result->getContent());
    }

    /**
     * @return integer
     */
    public function testView()
    {
        $response = $this->client->requestGrid(
            'rfp-requests-grid',
            [
                'rfp-requests-grid[_filter][firstName][value]' => LoadRequestData::FIRST_NAME,
                'rfp-requests-grid[_filter][LastName][value]' => LoadRequestData::LAST_NAME,
                'rfp-requests-grid[_filter][email][value]' => LoadRequestData::EMAIL,
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            sprintf('%s %s - Requests For Quote - Sales', LoadRequestData::FIRST_NAME, LoadRequestData::LAST_NAME),
            $result->getContent()
        );

        return $id;
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testInfo($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_info', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(LoadRequestData::FIRST_NAME, $result->getContent());
        $this->assertContains(LoadRequestData::LAST_NAME, $result->getContent());
        $this->assertContains(LoadRequestData::EMAIL, $result->getContent());
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testChangeStatus($id)
    {
        /** @var \OroB2B\Bundle\RFPBundle\Entity\RequestStatus $status */
        $status = $this->getContainer()->get('doctrine')->getRepository('OroB2BRFPBundle:RequestStatus')->findOneBy(
            ['deleted' => false],
            ['id' => 'DESC']
        );

        $this->assertNotNull($status);

        /** @var \OroB2B\Bundle\RFPBundle\Entity\Request $entity */
        $entity = $this->getContainer()->get('doctrine')->getRepository('OroB2BRFPBundle:Request')->find($id);

        $this->assertNotEquals($status->getId(), $entity->getStatus()->getId());
        $this->assertCount(0, $this->getNotesForRequest($entity));

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_change_status', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        $noteSubject = 'Test Request Note';

        $form = $crawler->selectButton('Update Request')->form();
        $form['orob2b_rfp_request_change_status[status]'] = $status->getId();
        $form['orob2b_rfp_request_change_status[note]'] = $noteSubject;

        $params = $form->getPhpValues();
        $params['_widgetContainer'] = 'dialog';

        $this->client->request($form->getMethod(), $form->getUri(), $params);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('RFQ Status was successfully changed', $result->getContent());

        /* @var $entity Request */
        $entity = $this->getContainer()->get('doctrine')->getRepository('OroB2BRFPBundle:Request')->find($id);

        $this->assertNotNull($entity);
        $this->assertNotNull($entity->getStatus());
        $this->assertEquals($status->getId(), $entity->getStatus()->getId());

        $notes = $this->getNotesForRequest($entity);
        $this->assertCount(1, $notes);

        $note = array_shift($notes);
        $this->assertTrue(strpos($note['subject'], $noteSubject) > 0);
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form->remove('orob2b_rfp_request[requestProducts][0]');

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request has been saved', $crawler->html());
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testCreateQuote($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_request_view', ['id' => $id]));

        $form = $crawler->selectButton('Create Quote')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('New Quote created', $crawler->html());
    }

    /**
     * @param Request $entity
     * @return ActivityList[]
     */
    private function getNotesForRequest(Request $entity)
    {
        /* @var $activityManager ActivityListManager */
        $activityManager = $this->getContainer()->get('oro_activity_list.manager');

        return $activityManager->getList(
            get_class($entity),
            $entity->getId(),
            [
                'activityType' => [
                    'value' => [
                        'Oro\Bundle\NoteBundle\Entity\Note'
                    ]
                ]
            ],
            1
        );
    }
}
