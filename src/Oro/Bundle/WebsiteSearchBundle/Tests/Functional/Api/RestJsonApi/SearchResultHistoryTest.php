<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart1Data;

class SearchResultHistoryTest extends RestJsonApiTestCase
{
    private ?bool $initialFeatureState;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadSearchResultHistoryPart1Data::class
        ]);

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
        $response = $this->cget(['entity' => 'searchresulthistories']);

        $this->assertResponseContains('cget_search_result_history.yml', $response, true);
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'searchresulthistories', 'id' => '<toString(@search_result_tires->id)>']);

        $this->assertResponseContains('get_search_result_history.yml', $response);
    }

    public function testGetRelationshipForLocalization(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchresulthistories',
            'id' => '<toString(@search_result_tires->id)>',
            'association' => 'localization'
        ]);

        $this->assertResponseContains(
            ['data' => ['type' => 'localizations', 'id' => '<toString(@en_US->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomer(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchresulthistories',
            'id' => '<toString(@search_result_tires->id)>',
            'association' => 'customer'
        ]);

        $this->assertResponseContains(
            ['data' => ['type' => 'customers', 'id' => '<toString(@customer.level_1->id)>']],
            $response
        );
    }

    public function testGetRelationshipForCustomerUser(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchresulthistories',
            'id' => '<toString(@search_result_tires->id)>',
            'association' => 'customerUser'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id' => '<toString(@grzegorz.brzeczyszczykiewicz@example.com->id)>'
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForOrganization(): void
    {
        $response = $this->getRelationship([
            'entity' => 'searchresulthistories',
            'id' => '<toString(@search_result_tires->id)>',
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
            'entity' => 'searchresulthistories',
            'id' => '<toString(@search_result_tires->id)>',
            'association' => 'owner'
        ]);

        $this->assertResponseContains(
            ['data' => ['type' => 'businessunits', 'id' => '<toString(@business_unit->id)>']],
            $response
        );
    }
}
