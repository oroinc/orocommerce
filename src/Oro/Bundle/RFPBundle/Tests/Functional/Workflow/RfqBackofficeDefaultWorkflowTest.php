<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Workflow;

use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RfqBackofficeDefaultWorkflowTest extends AbstractRfpDefaultWorkflowTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = $this->getReference(LoadRequestData::REQUEST1);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testWorkflowIsStarted()
    {
        $this->assertWorkflowIsStarted($this->request);
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
        $this->request = $this->refreshRequestEntity($this->request);
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
     * @inheritDoc
     */
    protected function getWorkflowName()
    {
        return 'rfq_backoffice_default';
    }

    /**
     * {@inheritdoc}
     */
    protected function getWidgetRouteName()
    {
        return 'oro_workflow_widget_entity_workflows';
    }

    /**
     * {@inheritdoc}
     */
    protected function getButtonTitles()
    {
        return [
            'Start',
            'Process',
            'Request More Information',
            'Delete',
            'Decline',
            'Reprocess',
            'Undelete',
        ];
    }

    /**
     * @param string $html
     */
    protected function assertContainsWorkflowTitle($html)
    {
        $this->assertContains('RFQ Backoffice', $html);
    }
}
