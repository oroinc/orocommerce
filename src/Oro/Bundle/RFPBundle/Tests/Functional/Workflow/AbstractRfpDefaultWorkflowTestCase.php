<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractRfpDefaultWorkflowTestCase extends WebTestCase
{
    /**
     * @var Request
     */
    protected $request;

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

    /**
     * @return string
     */
    abstract protected function getWorkflowName();

    /**
     * @return string
     */
    abstract protected function getWidgetRouteName();

    /**
     * @return array
     */
    abstract protected function getButtonTitles();

    /**
     * @param string $html
     */
    abstract protected function assertContainsWorkflowTitle($html);

    /**
     * @param Request $request
     */
    protected function assertWorkflowIsStarted(Request $request)
    {
        $manager = $this->getContainer()->get('oro_workflow.manager');
        $this->assertTrue($manager->isActiveWorkflow($this->getWorkflowName()));
        $this->assertNotNull($manager->getWorkflowItem($request, $this->getWorkflowName()));
    }

    /**
     * @param Request $request
     *
     * @return null|Crawler
     */
    protected function openRequestWorkflowWidget(Request $request)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl($this->getWidgetRouteName(), [
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

    /**
     * @param Request $request
     *
     * @return null|Crawler
     */
    protected function openRequestPage(Request $request)
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
    protected function assertButtonsAvailable(Request $request, array $buttonTitles)
    {
        $crawler = $this->openRequestWorkflowWidget($request);
        foreach ($buttonTitles as $title) {
            $this->assertNotEmpty($crawler->selectLink($title));
        }

        foreach (array_diff($this->getButtonTitles(), $buttonTitles) as $title) {
            $this->assertEmpty($crawler->selectLink($title));
        }

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    /**
     * @param Request $request
     *
     * @return Request
     */
    protected function refreshRequestEntity(Request $request)
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Request::class);

        return $em->find(Request::class, $request->getId());
    }

    /**
     * @param Request $request
     */
    protected function assertStartWorkflow(Request $request)
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
     *
     * @return array
     */
    protected function transit(Request $request, $linkTitle)
    {
        $crawler = $this->openRequestWorkflowWidget($request);

        $this->assertContainsWorkflowTitle($crawler->html());
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
}
