<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class RFPBackendWorkflowsTest extends WebTestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadRequestData::class,
            ]
        );

        $this->request = $this->getReference(LoadRequestData::REQUEST1);
    }

    public function testStartWorkflow()
    {
        $this->assertButtonsAvailable($this->request, ['Start']);
        $this->assertStartWorkflow($this->request);
        $this->assertButtonsAvailable($this->request, ['Request More Information', 'Decline', 'Delete']);
    }

    public function testMoreInfoRequest()
    {
        $crawler = $this->openRequestWorkflowWidget($this->request);

        $link = $crawler->selectLink('Request More Information');
        $this->assertNotEmpty($link, 'Transit button not found');

        $dialogUrl = $link->attr('data-dialog-url');
        $this->assertNotEmpty($dialogUrl);
        $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->generateWsseAuthHeader());
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $button = $crawler->selectButton('Submit');
        $form = $button->form(['oro_workflow_transition[notes]' => 'test notes']);
        $this->client->followRedirects(true);
        $this->client->submit($form);
        $this->assertContains('transitionSuccess = true', $this->client->getResponse()->getContent());

        // check that notes added and status changed
        $crawler = $this->openRequestPage($this->request);
        $this->assertEquals('more_info_requested', $this->request->getInternalStatus()->getId());
        $this->assertContains('test notes', $crawler->html());
        $this->assertButtonsAvailable($this->request, ['Delete']);
    }

    public function testDelete()
    {
        $this->transit($this->request, 'Delete');
        $this->assertEquals('deleted', $this->request->getInternalStatus()->getId());
        $this->assertButtonsAvailable($this->request, ['Undelete']);
    }

    public function testUndelete()
    {
        $this->transit($this->request, 'Undelete');
        $this->assertEquals('open', $this->request->getInternalStatus()->getId());
        $this->assertButtonsAvailable($this->request, ['Request More Information', 'Decline', 'Delete']);
    }


    public function testDecline()
    {
        $this->transit($this->request, 'Decline');
        $this->assertEquals('declined', $this->request->getInternalStatus()->getId());
        $this->assertEquals('cancelled', $this->request->getCustomerStatus()->getId());
        $this->assertButtonsAvailable($this->request, ['Delete']);
    }


    /**
     * @param Request $request
     */
    private function assertStartWorkflow(Request $request)
    {
        $crawler = $this->openRequestWorkflowWidget($request);

        $link = $crawler->selectLink('Start');
        $this->assertNotEmpty($link, 'Start button not found');

        $this->client->request(
            'GET',
            $link->attr('data-transition-url'),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workflowItem', $data);
    }

    /**
     * @param Request $request
     * @param string
     * @return array
     */
    private function transit(Request $request, $linkTitle)
    {
        $crawler = $this->openRequestWorkflowWidget($request);

        $this->assertContains('RFQ Backoffice', $crawler->html());
        $link = $crawler->selectLink($linkTitle);
        $this->assertNotEmpty($link, 'Transit button not found');

        $this->client->request(
            'GET',
            $link->attr('data-transition-url'),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workflowItem', $data);
        $this->request = $this->refreshRequestEntity($request);

        return $data;
    }


    /**
     * @param Request $request
     * @return null|Crawler
     */
    private function openRequestWorkflowWidget(Request $request)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_entity_workflows', [
                '_widgetContainer' => 'dialog',
                'entityClass' => Request::class,
                'entityId' => $request->getId(),
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('RFQ Backoffice', $crawler->html());

        return $crawler;
    }

    /**
     * @param Request $request
     * @return null|Crawler
     */
    private function openRequestPage(Request $request)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_rfp_request_view', ['id' => $request->getId()]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @param Request $request
     * @param array $buttonTitles
     */
    private function assertButtonsAvailable(Request $request, array $buttonTitles)
    {
        $allButtons = [
            'Start',
            'Process',
            'Request More Information',
            'Delete',
            'Decline',
            'Reprocess',
            'Undelete',
        ];
        $crawler = $this->openRequestWorkflowWidget($request);
        foreach ($buttonTitles as $title) {
            $this->assertNotEmpty($crawler->selectLink($title));
        }

        foreach (array_diff($allButtons, $buttonTitles) as $title) {
            $this->assertEmpty($crawler->selectLink($title));
        }

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    /**
     * @param Request $request
     * @return Request
     */
    private function refreshRequestEntity(Request $request)
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Request::class);

        return $em->find(Request::class, $request->getId());
    }
}
