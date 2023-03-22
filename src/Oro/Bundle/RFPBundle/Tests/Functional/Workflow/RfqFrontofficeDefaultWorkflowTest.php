<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowNotFoundException;

class RfqFrontofficeDefaultWorkflowTest extends AbstractRfqFrontofficeDefaultWorkflowTest
{
    public function testApplicableWorkflows()
    {
        $this->assertEquals(
            [
                'b2b_rfq_frontoffice_default',
            ],
            array_keys($this->manager->getApplicableWorkflows(Request::class))
        );
    }

    public function testTransitBackofficeTransition()
    {
        $this->expectException(WorkflowNotFoundException::class);
        $this->expectExceptionMessage('Workflow "rfq_backoffice_default" not found');

        $backoffice = $this->systemManager->getWorkflow('rfq_backoffice_default');
        $item = $backoffice->getWorkflowItemByEntityId($this->request->getId());

        $this->manager->transit($item, 'process_transition');
    }

    public function testApiStartBackoffice()
    {
        $this->ajaxRequest('POST', $this->getUrl('oro_api_frontend_workflow_start', [
            'workflowName' => 'b2b_rfq_backoffice_default',
            'transitionName' => '__start__',
        ]));

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testApiTransitBackofficeTransition()
    {
        $backoffice = $this->systemManager->getWorkflow('b2b_rfq_backoffice_default');
        $item = $backoffice->getWorkflowItemByEntityId($this->request->getId());

        $this->ajaxRequest('POST', $this->getUrl('oro_api_frontend_workflow_transit', [
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
        $data = self::jsonToArray($result);
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

        $data = self::jsonToArray($this->client->getResponse()->getContent());
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
        $this->markTestSkipped('Skipped due to crawler bug. Covered by behat.');
        $this->request = $this->getReference(LoadRequestData::REQUEST7);

        $this->transitSystem(
            $this->request,
            'b2b_rfq_backoffice_default',
            'request_more_information_transition',
            ['notes' => 'admin notes ']
        );

        $crawler = $this->openEntityViewPage($this->request);
        $link = $this->getTransitionLink(
            $crawler,
            $this->getTransitionLinkId($this->getWorkflowName(), 'provide_more_information_transition')
        );
        $this->assertNotEmpty($link, 'Transit button not found');

        $result = $this->transitWeb($link, ['oro_workflow_transition[notes]' => 'customer notes']);
        $this->assertNotEmpty($result, 'Transit failed');
        $this->assertContains('transitionSuccess', $result);

        $this->request = $this->refreshEntity($this->request);
        $this->assertEquals('submitted', $this->request->getCustomerStatus()->getId());
        $this->assertEquals('open', $this->request->getInternalStatus()->getId());

        $crawler = $this->openEntityViewPage($this->request);
        static::assertStringContainsString('customer notes', $crawler->html());
    }

    /**
     * {@inheritdoc}
     */
    protected function getWorkflowName()
    {
        return 'b2b_rfq_frontoffice_default';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserEmail()
    {
        return LoadUserData::ACCOUNT1_USER1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBasicAuthHeader()
    {
        return self::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
    }

    /**
     * {@inheritdoc}
     */
    protected function getWsseAuthHeader()
    {
        return self::generateWsseAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
    }

    private function getExpectedTransitions(): array
    {
        return [
            '__start__',
            'more_information_requested_transition',
            'provide_more_information_transition',
            'cancel_transition',
            'decline_transition',
            'resubmit_transition',
            'reopen_transition',
        ];
    }
}
