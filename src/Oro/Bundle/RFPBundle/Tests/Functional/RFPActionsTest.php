<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData;

/**
 * @dbIsolation
 */
class RFPActionsTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData'
            ]
        );
    }

    public function testChangeStatus()
    {
        /** @var \Oro\Bundle\RFPBundle\Entity\Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);

        /** @var \Oro\Bundle\RFPBundle\Entity\RequestStatus $status */
        if ($request->getStatus()->getName() === LoadRequestStatusData::NAME_IN_PROGRESS) {
            $status = $this->getReference('request.status.' . LoadRequestStatusData::NAME_NOT_DELETED);
        } else {
            $status = $this->getReference('request.status.' . LoadRequestStatusData::NAME_IN_PROGRESS);
        }

        $this->assertNotNull($status);

        $this->assertNotEquals($status->getName(), $request->getStatus()->getName());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    'operationName' => 'oro_rfp_change_status',
                    'entityClass' => 'Oro\Bundle\RFPBundle\Entity\Request',
                    'entityId' => $request->getId()
                ]
            ),
            ['_widgetContainer' => 'dialog']
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Update Request')->form();
        $form['oro_action_operation[request_status]'] = $status->getId();
        $form['oro_action_operation[request_note]'] = 'Test Request Note';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('widget.trigger(\'formSave\', {"success":true});', $crawler->html());

        /** @var Request $requestUpdated */
        $requestUpdated = $this->getReference(LoadRequestData::REQUEST1);
        $this->assertEquals($status->getName(), $requestUpdated->getStatus()->getName());
    }
}
