<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolation
 */
class RFPBackendWorkflowsTest extends WebTestCase
{
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
    }

    public function testWorkflow()
    {
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $this->assertStartWorkflow($request);

        $crawler = $this->openRequestPage($request);
        $this->assertContains('RFQ Backoffice', $crawler->html());
        $this->transit($request, 'Request More Information');
    }

    /**
     * @param Request $request
     */
    private function assertStartWorkflow(Request $request)
    {
        $crawler = $this->openRequestPage($request);

        $this->assertContains('RFQ Backoffice', $crawler->html());
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
     */
    private function transit(Request $request, $linkTitle)
    {
        $crawler = $this->openRequestPage($request);

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
    }


    /**
     * @param Request $request
     * @return null|Crawler
     */
    private function openRequestPage(Request $request)
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

        return $crawler;
    }
}
