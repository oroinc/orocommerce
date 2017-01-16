<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolation
 */
class RequestControllerDeletedInternalStatusTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadRequestData::class
            ]
        );
    }

    public function testViewInternalStatusDeletedNotFound()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        $id = $request->getId();

        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadUserData::PARENT_ACCOUNT_USER1, LoadUserData::PARENT_ACCOUNT_USER1)
        );

        $requestUrl = $this->getUrl('oro_rfp_frontend_request_view', ['id' => $id]);

        $this->assertStatus($requestUrl, 200);

        $this->transit($request, 'Delete');

        $this->assertStatus($requestUrl, 404);
    }

    /**
     * @param string $url
     * @param integer $status
     */
    private function assertStatus($url, $status)
    {
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, $status);
    }

    /**
     * @param Request $request
     * @param string
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
            self::generateWsseAuthHeader()
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workflowItem', $data);
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
            self::generateBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('RFQ Backoffice', $crawler->html());

        return $crawler;
    }
}
