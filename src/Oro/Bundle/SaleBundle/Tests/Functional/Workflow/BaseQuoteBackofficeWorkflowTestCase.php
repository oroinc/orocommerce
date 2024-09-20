<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Workflow;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class BaseQuoteBackofficeWorkflowTestCase extends WebTestCase
{
    protected const WORKFLOW_NAME = '';
    protected const WORKFLOW_TITLE = '';
    protected const WORKFLOW_BUTTONS = [];
    protected const TRANSITIONS = [];

    protected WorkflowManager $manager;
    protected WorkflowManager $systemManager;
    protected Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadQuoteData::class]);

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
        $this->systemManager = $this->getContainer()->get('oro_workflow.manager.system');
        $this->quote = $this->getReference(LoadQuoteData::QUOTE1);
    }

    protected function assertApplicableWorkflows(): void
    {
        if ($this->manager->isActiveWorkflow(static::WORKFLOW_NAME)) {
            $this->manager->deactivateWorkflow(static::WORKFLOW_NAME);
            $this->manager->resetWorkflowData(static::WORKFLOW_NAME);
        }
        $this->activateWorkflow();
        $this->assertEquals(
            [
                static::WORKFLOW_NAME,
            ],
            array_keys($this->manager->getApplicableWorkflows(Quote::class))
        );
    }

    public function testIsWorkflowStarted()
    {
        $this->assertNotNull($this->manager->getWorkflowItem($this->quote, static::WORKFLOW_NAME));
    }

    public function testWorkflowTransitions()
    {
        /** @var Workflow $workflow */
        $workflow = $this->manager->getWorkflow(static::WORKFLOW_NAME);

        $transitionManager = $workflow->getTransitionManager();

        $this->assertEqualsCanonicalizing(
            static::TRANSITIONS,
            array_keys($transitionManager->getTransitions()->toArray())
        );
    }

    public function testDeleteFromDraftStep()
    {
        $this->assertStatuses('draft', 'open');
        $this->assertBackofficeTransition('Delete', 'deleted', 'open', ['Undelete']);
    }

    /**
     * @depends testDeleteFromDraftStep
     */
    public function testUndeleteToDraftStep()
    {
        $this->assertStatuses('deleted', 'open');
        $this->assertBackofficeTransition(
            'Undelete',
            'draft',
            'open',
            ['Edit', 'Clone', 'Delete', 'Send to Customer']
        );
    }

    /**
     * @depends testUndeleteToDraftStep
     */
    public function testCloneFromDraftStep()
    {
        $this->assertStatuses('draft', 'open');
        $this->assertBackofficeTransition('Clone', 'draft', 'open', ['Edit', 'Clone', 'Delete', 'Send to Customer']);
    }

    /**
     * @depends testCloneFromDraftStep
     */
    public function testSendToCustomerFromDraftStep()
    {
        $this->assertStatuses('draft', 'open');

        $this->assertSendToCustomer();
    }

    /**
     * @depends testSendToCustomerFromDraftStep
     */
    public function testCancelFromSendToCustomerStep()
    {
        $this->assertStatuses('sent_to_customer', 'open');
        $this->assertBackofficeTransition(
            'Cancel',
            'cancelled',
            'open',
            ['Reopen']
        );
    }

    /**
     * @depends testCancelFromSendToCustomerStep
     */
    public function testReopenFromCancelStep()
    {
        $this->assertStatuses('cancelled', 'open');
        $this->assertBackofficeTransition(
            'Reopen',
            'cancelled',
            'open',
            ['Reopen']
        );
    }

    public function testExpireFromSendToCustomerStep()
    {
        $this->quote = $this->getReference(LoadQuoteData::QUOTE2);
        if (!$this->manager->getWorkflowItem($this->quote, static::WORKFLOW_NAME)) {
            $this->manager->startWorkflow(static::WORKFLOW_NAME, $this->quote);
        }
        $this->assertSendToCustomer();
        $this->assertBackofficeTransition(
            'Expire',
            'expired',
            'open',
            ['Reopen']
        );
    }

    public function testDeclineByCustomerFromSendToCustomerStep()
    {
        $this->quote = $this->getReference(LoadQuoteData::QUOTE3);
        if (!$this->manager->getWorkflowItem($this->quote, static::WORKFLOW_NAME)) {
            $this->manager->startWorkflow(static::WORKFLOW_NAME, $this->quote);
        }
        $this->assertSendToCustomer();
        $this->assertBackofficeTransition(
            'Declined by Customer',
            'declined',
            'open',
            ['Reopen']
        );
    }

    public function testCreateNewQuoteFromSendToCustomerStep()
    {
        $this->quote = $this->getReference(LoadQuoteData::QUOTE4);
        if (!$this->manager->getWorkflowItem($this->quote, static::WORKFLOW_NAME)) {
            $this->manager->startWorkflow(static::WORKFLOW_NAME, $this->quote);
        }
        $this->assertSendToCustomer();

        $crawler = $this->openQuoteWorkflowWidget();
        $link = $this->selectExactLink('Create new Quote', $crawler);

        $this->assertNotEmpty($link, 'Transit button not found (Create new Quote)');

        $dialogUrl = $link->attr('data-dialog-url');
        $this->assertNotEmpty($dialogUrl);

        $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->generateWsseAuthHeader());
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Submit')->form();
        $this->client->submit($form);

        static::assertStringContainsString('transitionSuccess', $this->client->getResponse()->getContent());
    }

    protected function assertStatuses(string $internalStatus, string $customerStatus): void
    {
        $this->refreshQuoteEntity();
        $this->assertEquals($internalStatus, $this->quote->getInternalStatus()->getInternalId());
        $this->assertEquals($customerStatus, $this->quote->getCustomerStatus()->getInternalId());
    }

    protected function assertBackofficeTransition(
        string $button,
        string $internalStatus,
        string $customerStatus,
        array $availableButtons
    ): void {
        if ($button) {
            $this->transit($button);
        }
        $this->assertStatuses($internalStatus, $customerStatus);
        $this->assertButtonsAvailable($availableButtons);
    }

    protected function transit(string $linkTitle): array
    {
        $crawler = $this->openQuoteWorkflowWidget();

        static::assertStringContainsString(static::WORKFLOW_TITLE, $crawler->html());
        $link = $this->selectExactLink($linkTitle, $crawler);
        $this->assertNotEmpty($link, 'Transit button not found ' . $linkTitle);

        $this->ajaxRequest(
            'POST',
            $link->attr('data-transition-url'),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $data = self::jsonToArray($this->client->getResponse()->getContent());
        $this->assertArrayHasKey('workflowItem', $data);
        $this->refreshQuoteEntity();

        return $data;
    }

    protected function openQuoteWorkflowWidget(): Crawler
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_entity_workflows', [
                '_widgetContainer' => 'dialog',
                'entityClass' => Quote::class,
                'entityId' => $this->quote->getId(),
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString(static::WORKFLOW_TITLE, $crawler->html());

        return $crawler;
    }

    protected function assertButtonsAvailable(array $buttonTitles): void
    {
        $crawler = $this->openQuoteWorkflowWidget();
        foreach ($buttonTitles as $title) {
            $this->assertNotNull($this->selectExactLink($title, $crawler), 'Not found button ' . $title);
        }

        foreach (array_diff(static::WORKFLOW_BUTTONS, $buttonTitles) as $title) {
            $this->assertNull($this->selectExactLink($title, $crawler), 'Not expected button - ' . $title);
        }
    }

    protected function refreshQuoteEntity(): void
    {
        $this->quote = $this->getContainer()->get('doctrine')->getManagerForClass(Quote::class)->find(
            Quote::class,
            $this->quote->getId()
        );
    }

    protected function transitWithForm(string $linkTitle, array $formData): void
    {
        $crawler = $this->openQuoteWorkflowWidget();
        $link = $this->selectExactLink($linkTitle, $crawler);

        $this->assertNotEmpty($link, sprintf('Transit button not found (%s)', $linkTitle));

        $dialogUrl = $link->attr('data-dialog-url');
        $this->assertNotEmpty($dialogUrl);

        $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->generateWsseAuthHeader());
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);

        $formNode = $crawler->filter('form[name=oro_workflow_transition]');
        $form = $formNode->form($formData);

        $this->client->submit($form);

        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('transitionSuccess', $this->client->getResponse()->getContent());
    }

    protected function assertSendToCustomer(): void
    {
        $this->transitWithForm('Send to Customer', ['oro_workflow_transition[email][to]' => 'test_email@test.tst']);
        $this->assertStatuses('sent_to_customer', 'open');
        $this->assertButtonsAvailable(['Expire', 'Cancel', 'Delete', 'Create new Quote']);
    }

    protected function activateWorkflow(): void
    {
        $this->manager->activateWorkflow(static::WORKFLOW_NAME);
        $this->manager->startWorkflow(static::WORKFLOW_NAME, $this->quote);
    }

    protected function selectExactLink(string $title, Crawler $crawler): ?Crawler
    {
        $link = $crawler->selectLink($title);
        for ($i = 0; $i < $link->count(); $i++) {
            if (trim($link->eq($i)->attr('data-transition-label')) === $title) {
                return $link->eq($i);
            }
        }

        return null;
    }
}
