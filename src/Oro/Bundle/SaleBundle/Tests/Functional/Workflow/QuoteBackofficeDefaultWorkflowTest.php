<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Workflow;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteBackofficeDefaultWorkflowTest extends WebTestCase
{
    const WORKFLOW_BUTTONS = [
        'Edit',
        'Clone',
        'Delete',
        'Undelete',
        'Send to Customer',
        'Cancel',
        'Expire',
        'Create new Quote',
        'Accept',
        'Decline',
        'Decline by Customer',
        'Reopen',
    ];

    const TRANSITIONS = [
        'edit_transition',
        'clone_transition',
        'delete_transition',
        'undelete_transition',
        'send_to_customer_transition',
        'cancel_transition',
        'expire_transition',
        'create_new_quote_transition',
        'accept_transition',
        'decline_transition',
        'decline_by_customer_transition',
        'reopen_transition',
        '__start__',
    ];

    /** @var WorkflowManager */
    private $manager;

    /** @var WorkflowManager */
    private $systemManager;

    /** @var Quote */
    private $quote;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadQuoteData::class]);

        $this->manager = $this->getContainer()->get('oro_workflow.manager');
        $this->systemManager = $this->getContainer()->get('oro_workflow.manager.system');
        $this->quote = $this->getReference(LoadQuoteData::QUOTE1);
        $this->manager = $this->getContainer()->get('oro_workflow.manager');
    }

    public function testApplicableWorkflows()
    {
        $this->assertEquals(
            [
                'b2b_quote_backoffice_default',
            ],
            array_keys($this->manager->getApplicableWorkflows(Quote::class))
        );
    }

    public function testIsWorkflowStarted()
    {
        $this->assertNotNull($this->manager->getWorkflowItem($this->quote, 'b2b_quote_backoffice_default'));
    }

    public function testWorkflowTransitions()
    {
        /** @var Workflow $workflow */
        $workflow = $this->manager->getWorkflow('b2b_quote_backoffice_default');

        /** @var TransitionManager $transitionManager */
        $transitionManager = $workflow->getTransitionManager();

        $this->assertEquals(
            self::TRANSITIONS,
            array_keys($transitionManager->getTransitions()->toArray())
        );
    }

    public function testDeleteFromOpenStep()
    {
        $this->assertStatuses('open', 'open');
        $this->assertBackofficeTransition('Delete', 'deleted', 'open', ['Undelete']);
    }

    /**
     * @depends testDeleteFromOpenStep
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
        $this->assertSendToCustomer();
        $crawler = $this->openQuoteWorkflowWidget($this->quote);
        $link = $crawler->selectLink('Create new Quote');
        $this->assertNotEmpty($link, 'Transit button not found (Create new Quote)');
        $dialogUrl = $link->attr('data-dialog-url');
        $this->assertNotEmpty($dialogUrl);
        $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->generateWsseAuthHeader());
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $form = $crawler->selectButton('Submit')->form();
        $this->client->submit($form);
        $this->assertContains('transitionSuccess = true', $this->client->getResponse()->getContent());
    }

    /**
     * @param string $internalStatus
     * @param string $customerStatus
     */
    private function assertStatuses($internalStatus, $customerStatus)
    {
        $this->quote = $this->refreshQuoteEntity($this->quote);
        $this->assertEquals($internalStatus, $this->quote->getInternalStatus()->getId());
        $this->assertEquals($customerStatus, $this->quote->getCustomerStatus()->getId());
    }

    /**
     * @param string $button
     * @param string $internalStatus
     * @param string $customerStatus
     * @param array $availableButtons
     */
    private function assertBackofficeTransition($button, $internalStatus, $customerStatus, array $availableButtons)
    {
        if ($button) {
            $this->transit($this->quote, $button);
        }
        $this->assertStatuses($internalStatus, $customerStatus);
        $this->assertButtonsAvailable($this->quote, $availableButtons);
    }

    /**
     * @param Quote $quote
     * @param string $linkTitle
     * @return array
     */
    private function transit(Quote $quote, $linkTitle)
    {
        $crawler = $this->openQuoteWorkflowWidget($quote);

        $this->assertContains('Quote Backoffice', $crawler->html());
        $link = $crawler->selectLink($linkTitle);
        $this->assertNotEmpty($link, 'Transit button not found ' . $linkTitle);

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
        $this->quote = $this->refreshQuoteEntity($quote);

        return $data;
    }

    /**
     * @param Quote $quote
     * @return null|Crawler
     */
    private function openQuoteWorkflowWidget(Quote $quote)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_widget_entity_workflows', [
                '_widgetContainer' => 'dialog',
                'entityClass' => Quote::class,
                'entityId' => $quote->getId(),
            ]),
            [],
            [],
            $this->generateBasicAuthHeader()
        );

        $this->assertNotEmpty($crawler->html());
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Quote Backoffice', $crawler->html());

        return $crawler;
    }

    /**
     * @param Quote $quote
     * @param array $buttonTitles
     */
    private function assertButtonsAvailable(Quote $quote, array $buttonTitles)
    {
        $crawler = $this->openQuoteWorkflowWidget($quote);
        foreach ($buttonTitles as $title) {
            $link = $crawler->selectLink($title);
            $this->assertTrue($link->count() && trim($link->attr('title')) === $title, 'Not found button ' . $title);
        }

        foreach (array_diff(self::WORKFLOW_BUTTONS, $buttonTitles) as $title) {
            $link = $crawler->selectLink($title);
            $this->assertTrue(
                !$link->count() || trim($link->attr('title')) !== $title,
                'Not expected button - ' . $title
            );
        }
    }

    /**
     * @param Quote $quote
     * @return Quote
     */
    private function refreshQuoteEntity(Quote $quote)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Quote::class)->find(
            Quote::class,
            $quote->getId()
        );
    }

    private function assertSendToCustomer()
    {
        $crawler = $this->openQuoteWorkflowWidget($this->quote);
        $link = $crawler->selectLink('Send to Customer');
        $this->assertNotEmpty($link, 'Transit button not found (Send to Customer)');
        $dialogUrl = $link->attr('data-dialog-url');
        $this->assertNotEmpty($dialogUrl);
        $crawler = $this->client->request('GET', $dialogUrl, [], [], $this->generateWsseAuthHeader());
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), 200);
        $formNode = $crawler->filter('form[name=oro_workflow_transition]');
        $form = $formNode->form(['oro_workflow_transition[email][to]' => 'test_email@test.tst']);
        $this->client->submit($form);
        $this->assertContains('transitionSuccess = true', $this->client->getResponse()->getContent());
        $this->assertStatuses('sent_to_customer', 'open');
        $this->assertButtonsAvailable($this->quote, ['Expire', 'Cancel', 'Delete', 'Create new Quote']);
    }
}
