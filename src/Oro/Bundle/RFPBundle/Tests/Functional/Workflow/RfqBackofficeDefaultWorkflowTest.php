<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RfqBackofficeDefaultWorkflowTest extends WebTestCase
{
    const WORKFLOW_BUTTONS = [
        'Open',
        'Process',
        'Request More Information',
        'Delete',
        'Decline',
        'Reprocess',
        'Undelete',
        'Mark as Processed'
    ];

    /** @var WorkflowManager */
    private $manager;

    /** @var WorkflowManager */
    private $systemManager;

    /** @var Request */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadRequestData::class]);

        $this->updateUserSecurityToken(self::AUTH_USER);
        $this->getContainer()->get('request_stack')
            ->push(HttpRequest::create($this->getUrl('oro_default')));

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
        $this->systemManager = $this->getContainer()->get('oro_workflow.manager.system');
        $this->request = $this->getReference(LoadRequestData::REQUEST1);
        $this->manager = $this->getContainer()->get('oro_workflow.manager');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->request);

        parent::tearDown();
    }

    public function testApplicableWorkflows()
    {
        $this->assertEquals(
            [
                'b2b_rfq_backoffice_default',
            ],
            array_keys($this->manager->getApplicableWorkflows(Request::class))
        );
    }

    public function testTransitFrontofficeTransition()
    {
        $this->expectException(\Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException::class);
        $this->expectExceptionMessage('Workflow "b2b_rfq_frontoffice_default" not found');

        $frontoffice = $this->systemManager->getWorkflow('b2b_rfq_frontoffice_default');
        $item = $frontoffice->getWorkflowItemByEntityId($this->request->getId());

        $this->manager->transit($item, 'provide_more_information_transition');
    }

    public function testIsWorkflowStarted()
    {
        $this->assertNotNull($this->manager->getWorkflowItem($this->request, 'b2b_rfq_backoffice_default'));
    }

    public function testWorkflowTransitions()
    {
        /** @var Workflow $workflow */
        $workflow = $this->manager->getWorkflow('b2b_rfq_backoffice_default');

        /** @var TransitionManager $transitionManager */
        $transitionManager = $workflow->getTransitionManager();

        $this->assertEquals(
            $this->getExpectedTransitions(),
            array_keys($transitionManager->getTransitions()->toArray())
        );
    }

    public function testDeleteFromOpenStep()
    {
        $this->assertStatuses('open', 'submitted');
        $this->assertBackofficeTransition('Delete', 'deleted', 'submitted', ['Undelete']);
    }

    /**
     * @depends testDeleteFromOpenStep
     */
    public function testUndeleteToOpenStep()
    {
        $this->assertStatuses('deleted', 'submitted');
        $this->assertBackofficeTransition(
            'Undelete',
            'open',
            'submitted',
            ['Request More Information', 'Decline', 'Delete', 'Mark as Processed']
        );
    }

    /**
     * @depends testUndeleteToOpenStep
     */
    public function testDeclineFromOpenStep()
    {
        $this->assertStatuses('open', 'submitted');
        $this->assertBackofficeTransition('Decline', 'declined', 'cancelled', ['Delete', 'Reprocess']);
    }

    /**
     * @depends testDeclineFromOpenStep
     */
    public function testReprocessToOpenStep()
    {
        $this->assertStatuses('declined', 'cancelled');
        $this->assertBackofficeTransition(
            'Reprocess',
            'open',
            'submitted',
            ['Request More Information', 'Decline', 'Delete', 'Mark as Processed']
        );
    }

    /**
     * @depends testReprocessToOpenStep
     */
    public function testDeleteFromCancelled()
    {
        $this->assertStatuses('open', 'submitted');

        //set valid customer status that should be setted by external workflow
        $this->transitSystem($this->request, 'b2b_rfq_frontoffice_default', 'cancel_transition');

        $this->assertStatuses('cancelled_by_customer', 'cancelled');

        $this->assertBackofficeTransition('Delete', 'deleted', 'cancelled', ['Undelete']);
    }

    /**
     * @depends testDeleteFromCancelled
     */
    public function testUndeleteToCancelled()
    {
        $this->assertStatuses('deleted', 'cancelled');
        $this->assertBackofficeTransition('Undelete', 'cancelled_by_customer', 'cancelled', ['Delete', 'Reprocess']);
    }

    /**
     * @depends testUndeleteToCancelled
     */
    public function testReprocessFromCancelled()
    {
        $this->assertStatuses('cancelled_by_customer', 'cancelled');
        $this->assertBackofficeTransition(
            'Reprocess',
            'open',
            'submitted',
            ['Request More Information', 'Decline', 'Delete', 'Mark as Processed']
        );
    }

    /**
     * @depends testReprocessFromCancelled
     */
    public function testMoreInfoRequestFromOpenStep()
    {
        $this->assertStatuses('open', 'submitted');
        $this->assertButtonsAvailable(
            $this->request,
            ['Request More Information', 'Decline', 'Delete', 'Mark as Processed']
        );
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
        static::assertStringContainsString('transitionSuccess', $this->client->getResponse()->getContent());

        // check that notes added and status changed
        $this->assertBackofficeTransition(null, 'more_info_requested', 'submitted', ['Delete']);
        static::assertStringContainsString('test notes', $this->openRequestPage($this->request)->html());
    }

    /**
     * @depends testMoreInfoRequestFromOpenStep
     */
    public function testDeleteFromMoreInfoRequestedStep()
    {
        //set valid customer status that should be setted by external workflow
        $this->transitSystem($this->request, 'b2b_rfq_frontoffice_default', 'reopen_transition');
        $this->transitSystem($this->request, 'b2b_rfq_frontoffice_default', 'more_information_requested_transition');

        $this->assertBackofficeTransition('Delete', 'deleted', 'requires_attention', ['Undelete']);
    }

    /**
     * @depends testDeleteFromMoreInfoRequestedStep
     */
    public function testUndeleteToMoreInfoRequestedStep()
    {
        $this->assertBackofficeTransition('Undelete', 'more_info_requested', 'requires_attention', ['Delete']);
    }

    /**
     * @depends testUndeleteToMoreInfoRequestedStep
     */
    public function testToProcessedStep()
    {
        $this->transitSystem(
            $this->request,
            'b2b_rfq_frontoffice_default',
            'provide_more_information_transition',
            ['notes' => 'customer notes']
        );
        $this->transitSystem($this->request, 'b2b_rfq_backoffice_default', 'info_provided_transition');

        $this->assertBackofficeTransition('Mark as Processed', 'processed', 'submitted', ['Delete']);
    }

    /**
     * @return array
     */
    protected function getExpectedTransitions()
    {
        return [
            '__start__',
            'process_transition',
            'request_more_information_transition',
            'decline_transition',
            'cancel_transition',
            'delete_transition',
            'info_provided_transition',
            'reprocess_transition',
            'undelete_to_cancelled_transition',
            'undelete_to_open_transition',
            'undelete_to_more_information_requested_transition'
        ];
    }

    /**
     * @param string $internalStatus
     * @param string $customerStatus
     */
    protected function assertStatuses($internalStatus, $customerStatus)
    {
        $this->request = $this->refreshRequestEntity($this->request);
        $this->assertEquals($internalStatus, $this->request->getInternalStatus()->getId());
        $this->assertEquals($customerStatus, $this->request->getCustomerStatus()->getId());
    }

    /**
     * @param string $button
     * @param string $internalStatus
     * @param string $customerStatus
     * @param array $availableButtons
     */
    protected function assertBackofficeTransition($button, $internalStatus, $customerStatus, array $availableButtons)
    {
        if ($button) {
            $this->transit($this->request, $button);
        }
        $this->assertStatuses($internalStatus, $customerStatus);
        $this->assertButtonsAvailable($this->request, $availableButtons);
    }

    /**
     * @param Request $request
     * @param string  $linkTitle
     * @return array
     */
    private function transit(Request $request, $linkTitle)
    {
        $crawler = $this->openRequestWorkflowWidget($request);

        static::assertStringContainsString('RFQ Management Flow', $crawler->html());
        $link = $crawler->selectLink($linkTitle);
        $this->assertNotEmpty($link, 'Transit button not found');

        $this->ajaxRequest(
            'POST',
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
        static::assertStringContainsString('RFQ Management Flow', $crawler->html());

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

    private function assertButtonsAvailable(Request $request, array $buttonTitles)
    {
        $crawler = $this->openRequestWorkflowWidget($request);
        foreach ($buttonTitles as $title) {
            $this->assertNotEmpty($crawler->selectLink($title));
        }

        foreach (array_diff(self::WORKFLOW_BUTTONS, $buttonTitles) as $title) {
            $this->assertEmpty($crawler->selectLink($title));
        }
    }

    /**
     * @param Request $request
     * @return Request
     */
    private function refreshRequestEntity(Request $request)
    {
        return $this->getEntityManager(Request::class)->find(Request::class, $request->getId());
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    private function getEntityManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @param string $transitionName
     * @param array $transitionData
     */
    protected function transitSystem($entity, $workflowName, $transitionName, $transitionData = [])
    {
        /** @var $wi WorkflowItem */
        $wi = $this->manager->getWorkflowItem($entity, $workflowName);
        $this->assertNotNull($wi);

        $wi->getData()->add($transitionData);
        $this->systemManager->transit($wi, $transitionName);
    }
}
