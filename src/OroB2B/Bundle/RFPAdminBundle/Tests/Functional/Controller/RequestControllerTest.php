<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @dbReindex
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
                'OroB2B\Bundle\RFPAdminBundle\Tests\Functional\DataFixtures\LoadRequestData'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_rfp_admin_request_index'));
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
            $this->getUrl('orob2b_rfp_admin_request_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            sprintf('%s %s - Requests For Proposal - RFP', LoadRequestData::FIRST_NAME, LoadRequestData::LAST_NAME),
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
            $this->getUrl('orob2b_rfp_admin_request_info', ['id' => $id]),
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
        /** @var \Doctrine\Common\Persistence\ObjectManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        /** @var \OroB2B\Bundle\RFPAdminBundle\Entity\RequestStatus $status */
        $status = $manager->getRepository('OroB2BRFPAdminBundle:RequestStatus')->findOneBy(
            ['deleted' => false],
            ['id' => 'DESC']
        );

        $this->assertNotNull($status);

        /** @var \OroB2B\Bundle\RFPAdminBundle\Entity\Request $entity */
        $entity = $manager->getRepository('OroB2BRFPAdminBundle:Request')->find($id);

        $this->assertNotEquals($status->getId(), $entity->getStatus()->getId());
        $this->assertCount(0, $this->getNotesForRequest($entity));

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_admin_request_change_status', ['id' => $id]),
            ['_widgetContainer' => 'dialog']
        );

        $noteSubject = 'Test Request Note';

        $form = $crawler->selectButton('Update Request')->form();
        $form['orob2b_rfp_admin_request_change_status[status]'] = $status->getId();
        $form['orob2b_rfp_admin_request_change_status[note]'] = $noteSubject;

        $params = $form->getPhpValues();
        $params['_widgetContainer'] = 'dialog';

        $this->client->request($form->getMethod(), $form->getUri(), $params);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request For Proposal Status was successfully changed', $result->getContent());

        $manager->refresh($entity);

        $this->assertNotNull($entity);
        $this->assertNotNull($entity->getStatus());
        $this->assertEquals($status->getId(), $entity->getStatus()->getId());

        $notes = $this->getNotesForRequest($entity);
        $this->assertCount(1, $notes);

        $note = array_shift($notes);
        $this->assertTrue(strpos($note['subject'], $noteSubject) > 0);
    }

    /**
     * @param Request $entity
     * @return \Oro\Bundle\ActivityListBundle\Entity\ActivityList[]
     */
    private function getNotesForRequest(Request $entity)
    {
        /** @var \Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager $ActivityManager */
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
