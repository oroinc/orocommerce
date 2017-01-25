<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * @dbIsolation
 */
class RfqFrontofficeDefaultWorkflowTestCase extends FrontendWebTestCase
{
    /** @var Request */
    protected $request;

    /** @var array */
    protected $transitions = [];

    /** @var WorkflowManager */
    protected $manager;

    /** @var WorkflowManager */
    protected $systemManager;

    /** @var Workflow */
    protected $workflow;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->getBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadRequestData::class,
            ]
        );

        $this->updateCustomerUserSecurityToken(LoadUserData::ACCOUNT1_USER1);
        $this->getContainer()->get('request_stack')->push(new HttpRequest());

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
        $this->systemManager = $this->getContainer()->get('oro_workflow.manager.system');

        if (!$this->manager->isActiveWorkflow($this->getWorkflowName())) {
            $this->markTestSkipped(sprintf('The Workflow "%s" is inactive', $this->getWorkflowName()));
        }

        $this->workflow = $this->manager->getWorkflow($this->getWorkflowName());

        $this->request = $this->getReference(LoadRequestData::REQUEST2);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->request);

        parent::tearDown();
    }

    public function testApplicableWorkflows()
    {
        $this->assertEquals(
            [
                'rfq_frontoffice_default',
            ],
            array_keys($this->manager->getApplicableWorkflows(Request::class))
        );
    }

    /**
     * @expectedException Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException
     * @expectedExceptionMessage Workflow "rfq_backoffice_default" not found
     */
    public function testTransitBackofficeTransition()
    {
        $backoffice = $this->systemManager->getWorkflow('rfq_backoffice_default');
        $item = $backoffice->getWorkflowItemByEntityId($this->request->getId());

        $this->manager->transit($item, 'process_transition');
    }

    public function testApiStartBackoffice()
    {
        $this->client->request('GET', $this->getUrl('oro_api_frontend_workflow_start', [
            'workflowName' => 'rfq_backoffice_default',
            'transitionName' => '__start__',
        ]));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testApiTransitBackofficeTransition()
    {
        $backoffice = $this->systemManager->getWorkflow('rfq_backoffice_default');
        $item = $backoffice->getWorkflowItemByEntityId($this->request->getId());

        $this->client->request('GET', $this->getUrl('oro_api_frontend_workflow_transit', [
            'workflowItemId' => $item->getId(),
            'transitionName' => 'process_transition',
        ]));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testIsWorkflowStarted()
    {
        $this->assertNotNull($this->manager->getWorkflowItem($this->request, $this->getWorkflowName()));
    }

    public function testWorkflowTransitions()
    {
        /** @var TransitionManager $transitionManager */
        $transitionManager = $this->workflow->getTransitionManager();

        $this->assertEquals(
            $this->getExpectedTransitions(),
            array_keys($transitionManager->getTransitions()->toArray())
        );
    }

    public function testCancelTransition()
    {
        $crawler = $this->openEntityViewPage($this->request);
        $link = $this->getTransitionLink(
            $crawler,
            $this->getTransitionLinkId($this->getWorkflowName(), 'cancel_transition')
        );
        $this->assertNotEmpty($link, 'Transit button not found');
        $result = $this->transitWeb($link);
        $this->assertNotEmpty($result, 'Transit failed');
        $data = json_decode($result, true);
        $this->assertArrayHasKey('workflowItem', $data);
        $this->request = $this->refreshEntity($this->request);
        $this->assertEquals('cancelled', $this->request->getCustomerStatus()->getId());
        $this->assertEquals('cancelled_by_customer', $this->request->getInternalStatus()->getId());
    }

    /**
     * @depends testCancelTransition
     */
    public function testResubmitTransition()
    {
        $crawler = $this->openEntityViewPage($this->request);
        $link = $this->getTransitionLink(
            $crawler,
            $this->getTransitionLinkId($this->getWorkflowName(), 'resubmit_transition')
        );
        $this->assertNotEmpty($link, 'Transit button not found');
        $result = $this->transitWeb($link);
        $this->assertNotEmpty($result, 'Transit failed');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workflowItem', $data);
        $workflowItem = $data['workflowItem'];
        $this->assertArrayHasKey('workflow_name', $workflowItem);
        $this->assertEquals($this->getWorkflowName(), $workflowItem['workflow_name']);
        $this->assertArrayHasKey('entity_id', $workflowItem);
        $this->assertArrayHasKey('entity_class', $workflowItem);

        //Check Old Request statuses
        $this->assertEquals('cancelled', $this->request->getCustomerStatus()->getId());
        $this->assertEquals('cancelled_by_customer', $this->request->getInternalStatus()->getId());

        /** @var Request $newRequest */
        $newRequest = $this->getEntity($workflowItem['entity_class'], $workflowItem['entity_id'] + 1);
        $this->assertNotNull($newRequest);
        $this->assertEquals('submitted', $newRequest->getCustomerStatus()->getId());
        $this->assertEquals('open', $newRequest->getInternalStatus()->getId());
    }

    public function testProvideMoreInformationTransition()
    {
        $this->request = $this->getReference(LoadRequestData::REQUEST7);

        $this->transitSystem(
            $this->request,
            'rfq_backoffice_default',
            'request_more_information_transition',
            ['notes' => 'admin notes ']
        );

        $this->transitSystem(
            $this->request,
            $this->getWorkflowName(),
            'more_information_requested_transition'
        );

        $crawler = $this->openEntityViewPage($this->request);
        $link = $this->getTransitionLink(
            $crawler,
            $this->getTransitionLinkId($this->getWorkflowName(), 'provide_more_information_transition')
        );
        $this->assertNotEmpty($link, 'Transit button not found');

        $result = $this->transitWeb($link, ['oro_workflow_transition[notes]' => 'customer notes']);
        $this->assertNotEmpty($result, 'Transit failed');
        $this->assertContains('transitionSuccess = true', $result);

        $this->request = $this->refreshEntity($this->request);
        $this->assertEquals('submitted', $this->request->getCustomerStatus()->getId());
        $this->assertEquals('open', $this->request->getInternalStatus()->getId());

        $crawler = $this->openEntityViewPage($this->request);
        $this->assertContains('customer notes', $crawler->html());
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @param string $transitionName
     * @param array $transitionData
     */
    protected function transitSystem($entity, $workflowName, $transitionName, $transitionData = [])
    {
        /* @var $wi WorkflowItem */
        $wi = $this->systemManager->getWorkflowItem($entity, $workflowName);
        $this->assertNotNull($wi);
        $wi->getData()->add($transitionData);
        $this->systemManager->transit($wi, $transitionName);
    }

    /**
     * @param Crawler $link
     * @param array $formValues
     * @param string $submitButton
     *
     * @return string
     */
    protected function transitWeb(Crawler $link, $formValues = [], $submitButton = 'Submit')
    {
        $dialogUrl = $link->attr('data-dialog-url');
        $transitionUrl = $link->attr('data-transition-url');
        if ($dialogUrl) {
            $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->getWsseAuthHeader());
            $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
            $button = $crawler->selectButton($submitButton);
            $form = $button->form($formValues);
            $this->client->followRedirects(true);
            $this->client->submit($form);

            return $this->client->getResponse()->getContent();
        } else {
            $this->client->request('GET', $transitionUrl, [], [], $this->getWsseAuthHeader());
            $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

            return $this->client->getResponse()->getContent();
        }
    }

    /**
     * @param Request $request
     *
     * @return Crawler
     */
    protected function openEntityViewPage(Request $request)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl($this->getEntityViewRoute(), ['id' => $request->getId()]),
            [],
            [],
            $this->getBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'rfq_frontoffice_default';
    }

    /**
     * @param string $workflowName
     * @param string $transitionName
     *
     * @return string
     */
    protected function getTransitionLinkId($workflowName, $transitionName)
    {
        return sprintf('transition-%s-%s', $workflowName, $transitionName);
    }

    /**
     * @param Crawler $crawler
     * @param $transitionLinkId
     *
     * @return Crawler
     */
    protected function getTransitionLink(Crawler $crawler, $transitionLinkId)
    {
        $result = $crawler->filter(sprintf('a#%s', $transitionLinkId));

        return $result;
    }

    /**
     * @return array
     */
    protected function getBasicAuthHeader()
    {
        return self::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
    }

    /**
     * @return array
     */
    protected function getWsseAuthHeader()
    {
        return self::generateWsseAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
    }

    /**
     * @return array
     */
    protected function getExpectedTransitions()
    {
        return [
            '__start__',
            'more_information_requested_transition',
            'provide_more_information_transition',
            'cancel_transition',
            'resubmit_transition',
            'reopen_transition',
        ];
    }

    /**
     * @return string
     */
    protected function getEntityViewRoute()
    {
        return 'oro_rfp_frontend_request_view';
    }

    /**
     * @param object $entity
     *
     * @return object|null
     */
    protected function refreshEntity($entity)
    {
        $dh = $this->getContainer()->get('oro_entity.doctrine_helper');

        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($dh->getEntityClass($entity))
            ->find($dh->getEntityClass($entity), $dh->getEntityIdentifier($entity));
    }

    /**
     * @param string $entityClass
     * @param int|string $entityId
     *
     * @return null|object
     */
    protected function getEntity($entityClass, $entityId)
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass($entityClass)
            ->find($entityClass, $entityId);
    }
}
