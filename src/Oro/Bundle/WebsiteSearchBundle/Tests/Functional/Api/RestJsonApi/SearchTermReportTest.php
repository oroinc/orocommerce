<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchTermReportData;

class SearchTermReportTest extends RestJsonApiTestCase
{
    private ?bool $initialFeatureState;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadSearchTermReportData::class]);

        $configManager = self::getConfigManager();
        $this->initialFeatureState = $configManager->get('oro_website_search.enable_global_search_history_feature');
        $configManager->set('oro_website_search.enable_global_search_history_feature', true);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_website_search.enable_global_search_history_feature', $this->initialFeatureState);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'searchtermreports']);

        $this->assertResponseContains('cget_search_term_report.yml', $response, true);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'searchtermreports', 'id' => '<toString(@search_term_report_1->id)>']);

        $this->assertResponseContains('get_search_term_report.yml', $response);
    }

    public function testGetRelationshipForOrganization(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchtermreports',
            'id' => '<toString(@search_term_report_2->id)>',
            'association' => 'organization'
        ]);

        $this->assertResponseContains(
            ['data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']],
            $response
        );
    }

    public function testGetRelationshipForOwner(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchtermreports',
            'id' => '<toString(@search_term_report_2->id)>',
            'association' => 'owner'
        ]);

        $this->assertResponseContains(
            ['data' => ['type' => 'businessunits', 'id' => '<toString(@business_unit->id)>']],
            $response
        );
    }
}
