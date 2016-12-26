<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WorkflowDefinitionCheckoutTestCase extends WebTestCase
{
    const XPATH_RECORD_GROUPS = "//label[@class='control-label' and text()='Exclusive Record Groups']"
    . "/following-sibling::div//li";
    const XPATH_FLOW_NAME = "//label[@class='control-label' and text()='Name']/following-sibling::div/div";
    const XPATH_FLOWCHART = "//div[@class = 'workflow-flowchart-container']";

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @param string $definitionName
     * @param string $checkoutName
     * @param string $recordGroupsName
     */
    protected function assertCheckoutWorkflowCorrectViewPage($definitionName, $checkoutName, $recordGroupsName)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_workflow_definition_view', ['name' => $definitionName])
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filterXPath(self::XPATH_FLOWCHART)->count());
        $this->assertContains($checkoutName, $crawler->filterXPath(self::XPATH_FLOW_NAME)->text());
        $this->assertEquals($recordGroupsName, $crawler->filterXPath(self::XPATH_RECORD_GROUPS)->text());
    }
}
