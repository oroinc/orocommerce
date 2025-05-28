<?php

namespace Oro\Bundle\SameBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * @dbIsolationPerTest
 */
class QuoteWithoutActiveWorkflowsTest extends RestJsonApiTestCase
{
    private array $deactivatedWorkflows;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class, LoadRequestData::class]);

        $this->deactivatedWorkflows = $this->deactivateActiveWorkflows();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->activateWorkflows($this->deactivatedWorkflows);
        parent::tearDown();
    }

    private function getWorkflowRegistry(): WorkflowRegistry
    {
        return self::getContainer()->get('oro_workflow.registry');
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return self::getContainer()->get('oro_workflow.manager');
    }

    private function deactivateActiveWorkflows(): array
    {
        $deactivatedWorkflows = [];
        $workflowManager = $this->getWorkflowManager();
        $activeWorkflows = $this->getWorkflowRegistry()->getActiveWorkflowsByEntityClass(Quote::class);
        foreach ($activeWorkflows as $workflow) {
            $deactivatedWorkflows[] = $workflow->getName();
            $workflowManager->deactivateWorkflow($workflow->getName());
        }

        return $deactivatedWorkflows;
    }

    private function activateWorkflows(array $workflowNames): void
    {
        $workflowManager = $this->getWorkflowManager();
        foreach ($workflowNames as $workflowName) {
            $workflowManager->activateWorkflow($workflowName);
        }
    }

    public function testCreateWithInternalStatus(): void
    {
        // guard
        self::assertFalse($this->getWorkflowRegistry()->hasActiveWorkflowsByEntityClass(Quote::class));

        $data = [
            'data' => [
                'type' => 'quotes',
                'relationships' => [
                    'internalStatus' => [
                        'data' => ['type' => 'quoteinternalstatuses', 'id' => 'sent_to_customer']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'quotes'], $data);

        $quoteId = (int)$this->getResourceId($response);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);

        $this->assertResponseContains($data, $response);

        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }

    public function testTryToUpdateInternalStatus(): void
    {
        // guard
        self::assertFalse($this->getWorkflowRegistry()->hasActiveWorkflowsByEntityClass(Quote::class));

        $quoteId = $this->getReference('sale.quote.1')->getId();

        $data = [
            'data' => [
                'type' => 'quotes',
                'id' => (string)$quoteId,
                'relationships' => [
                    'internalStatus' => [
                        'data' => ['type' => 'quoteinternalstatuses', 'id' => 'sent_to_customer']
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'quotes', 'id' => (string)$quoteId], $data);

        $this->assertResponseContains($data, $response);

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }

    public function testUpdateInternalStatusViaRelationship(): void
    {
        // guard
        self::assertFalse($this->getWorkflowRegistry()->hasActiveWorkflowsByEntityClass(Quote::class));

        $quoteId = $this->getReference('sale.quote.1')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'internalStatus'],
            [
                'data' => [
                    'type' => 'quoteinternalstatuses',
                    'id' => 'sent_to_customer'
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }
}
